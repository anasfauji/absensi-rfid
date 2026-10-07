<?php

namespace Tests\Feature;

use App\Models\PerangkatRfid;
use App\Models\Permission;
use App\Models\Pengguna;
use App\Models\RfidEvent;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidEventReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_list_dan_detail_memerlukan_autentikasi(): void
    {
        $event = $this->buatEvent();

        $this->getJson('/api/rfid-event')->assertUnauthorized();
        $this->getJson("/api/rfid-event/{$event->id_rfid_event}")->assertUnauthorized();
    }

    public function test_admin_dan_operator_dapat_membaca_list_dan_detail(): void
    {
        $event = $this->buatEvent();

        foreach (['ADMIN', 'OPERATOR'] as $roleCode) {
            $pengguna = $this->buatPenggunaDenganRole($roleCode);

            $this->actingAs($pengguna, 'sanctum')
                ->getJson('/api/rfid-event')
                ->assertOk()
                ->assertJsonPath('data.rfid_event.0.id_rfid_event', $event->id_rfid_event);

            $this->actingAs($pengguna, 'sanctum')
                ->getJson("/api/rfid-event/{$event->id_rfid_event}")
                ->assertOk()
                ->assertJsonPath('data.rfid_event.id_rfid_event', $event->id_rfid_event);
        }
    }

    public function test_role_lain_ditolak_meski_memiliki_permission_rfid_event_lihat(): void
    {
        $this->buatEvent();

        foreach (['KS', 'WAKA', 'KAPROG', 'BK', 'WALI_KELAS', 'GURU'] as $roleCode) {
            $pengguna = $this->buatPenggunaDenganRole($roleCode);
            $this->assertTrue($pengguna->hasPermission('rfid_event.lihat'));

            $this->actingAs($pengguna, 'sanctum')
                ->getJson('/api/rfid-event')
                ->assertForbidden();
            $this->actingAs($pengguna, 'sanctum')
                ->getJson('/api/rfid-event/1')
                ->assertForbidden();
        }

        $siswa = $this->buatPenggunaDenganRole('SISWA');
        $this->actingAs($siswa, 'sanctum')
            ->getJson('/api/rfid-event')
            ->assertForbidden();
    }

    public function test_permission_ditegakkan_terpisah_dari_role(): void
    {
        $event = $this->buatEvent();
        $role = Role::query()->where('kode_role', 'OPERATOR')->firstOrFail();
        $role->permissions()->detach(
            Permission::query()->where('kode_permission', 'rfid_event.lihat')->value('id_permission')
        );
        $operator = $this->buatPenggunaDenganRole('OPERATOR');

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/rfid-event')
            ->assertForbidden();
        $this->actingAs($operator, 'sanctum')
            ->getJson("/api/rfid-event/{$event->id_rfid_event}")
            ->assertForbidden();
    }

    public function test_list_mengembalikan_raw_event_dan_empty_collection(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/rfid-event')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Daftar RFID event berhasil diambil.',
                'data' => ['rfid_event' => []],
            ]);

        $event = $this->buatEvent(null, null, 'DUPLIKAT');
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/rfid-event')
            ->assertOk()
            ->assertJsonPath('data.rfid_event.0.id_rfid_event', $event->id_rfid_event)
            ->assertJsonPath('data.rfid_event.0.hasil_event', 'DUPLIKAT')
            ->assertJsonPath('data.rfid_event.0.id_siswa', null)
            ->assertJsonPath('data.rfid_event.0.id_kartu_rfid', null);
    }

    public function test_event_ditolak_tetap_raw_event_dan_response_tidak_memuat_status_evaluasi(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $event = $this->buatEvent(null, null, 'DITOLAK');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/rfid-event/{$event->id_rfid_event}")
            ->assertOk()
            ->assertJsonPath('data.rfid_event.hasil_event', 'DITOLAK')
            ->assertJsonPath('data.rfid_event.uid_rfid', 'UNKNOWN-UID')
            ->assertJsonPath('data.rfid_event.status_proses', 'SELESAI');

        $record = $response->json('data.rfid_event');
        foreach ([
            'id_rfid_event', 'id_perangkat_rfid', 'id_kartu_rfid', 'id_siswa',
            'uid_rfid', 'waktu_event', 'waktu_diterima_server', 'status_proses',
            'hasil_event', 'keterangan', 'created_at', 'updated_at',
        ] as $field) {
            $this->assertArrayHasKey($field, $record);
        }
        foreach (['status_presensi', 'waktu_masuk', 'waktu_keluar', 'terlambat', 'hadir', 'status'] as $field) {
            $this->assertArrayNotHasKey($field, $record);
        }
        $this->assertDatabaseMissing('presensi_gate', [
            'id_siswa' => $event->id_siswa,
            'tanggal' => '2026-10-07',
        ]);
    }

    public function test_detail_tidak_ditemukan_menghasilkan_404(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/rfid-event/999999')
            ->assertNotFound();
    }

    private function buatEvent(?int $idSiswa = null, ?int $idKartu = null, string $hasil = 'VALID'): RfidEvent
    {
        $device = PerangkatRfid::query()->create([
            'kode_perangkat' => 'GATE-' . uniqid(),
            'nama_perangkat' => 'Gate test',
            'lokasi' => 'Gerbang test',
            'jenis_perangkat' => 'ESP32_RFID',
            'status' => 'AKTIF',
        ]);

        return RfidEvent::query()->create([
            'id_perangkat_rfid' => $device->id_perangkat_rfid,
            'id_kartu_rfid' => $idKartu,
            'id_siswa' => $idSiswa,
            'uid_rfid' => $hasil === 'DITOLAK' ? 'UNKNOWN-UID' : 'RAW-UID',
            'waktu_event' => '2026-10-07 07:15:00',
            'waktu_diterima_server' => '2026-10-07 07:15:01',
            'status_proses' => 'SELESAI',
            'hasil_event' => $hasil,
            'keterangan' => $hasil === 'DITOLAK' ? 'UID tidak dikenal.' : null,
        ]);
    }

    private function buatPenggunaDenganRole(string $roleCode): Pengguna
    {
        $pengguna = Pengguna::query()->create([
            'username' => strtolower($roleCode) . '-rfid-read-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $roleCode,
            'status' => 'AKTIF',
        ]);
        $role = Role::query()->where('kode_role', $roleCode)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }
}
