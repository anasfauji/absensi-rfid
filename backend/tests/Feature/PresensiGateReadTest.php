<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\PresensiGate;
use App\Models\Role;
use App\Models\Siswa;
use App\Services\AttendanceEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PresensiGateReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_list_memerlukan_autentikasi(): void
    {
        $this->getJson('/api/presensi-gate')->assertUnauthorized();
    }

    public function test_detail_memerlukan_autentikasi(): void
    {
        $this->getJson('/api/presensi-gate/1')->assertUnauthorized();
    }

    public function test_admin_dapat_membaca_seluruh_presensi_gate(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $gatePertama = $this->buatPresensiGate('2026-10-06');
        $gateKedua = $this->buatPresensiGate('2026-10-07');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/presensi-gate');

        $response->assertOk()
            ->assertJsonCount(2, 'data.presensi_gate');
        $this->assertEqualsCanonicalizing(
            [$gatePertama->id_presensi_gate, $gateKedua->id_presensi_gate],
            collect($response->json('data.presensi_gate'))
                ->pluck('id_presensi_gate')
                ->all()
        );
    }

    public function test_operator_dapat_membaca_seluruh_presensi_gate(): void
    {
        $operator = $this->buatPenggunaDenganRole('OPERATOR');
        $this->buatPresensiGate('2026-10-06');
        $this->buatPresensiGate('2026-10-07');

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/presensi-gate')
            ->assertOk()
            ->assertJsonCount(2, 'data.presensi_gate');
    }

    public function test_role_non_admin_operator_ditolak_meski_memiliki_permission_lihat(): void
    {
        foreach (['KS', 'WAKA', 'KAPROG', 'BK', 'WALI_KELAS', 'GURU', 'SISWA'] as $roleCode) {
            $pengguna = $this->buatPenggunaDenganRole($roleCode);

            $this->assertTrue($pengguna->hasPermission('presensi_gate.lihat'));
            $this->actingAs($pengguna, 'sanctum')
                ->getJson('/api/presensi-gate')
                ->assertForbidden();
        }
    }

    public function test_list_mengembalikan_evidence_operasional_tanpa_field_evaluasi_atau_keluar(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $gate = $this->buatPresensiGate('2026-10-06');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/presensi-gate');

        $response->assertOk()->assertJsonPath(
            'data.presensi_gate.0.id_presensi_gate',
            $gate->id_presensi_gate
        );

        $record = $response->json('data.presensi_gate.0');
        $this->assertSame($gate->id_siswa, $record['id_siswa']);
        $this->assertSame('2026-10-06', $record['tanggal']);
        $this->assertSame('2026-10-06 06:46:13', $record['waktu_masuk']);
        $this->assertSame('RFID', $record['sumber_masuk']);
        $this->assertArrayHasKey('created_at', $record);
        $this->assertArrayHasKey('updated_at', $record);
        $this->assertArrayNotHasKey('status', $record);
        $this->assertArrayNotHasKey('waktu_keluar', $record);
        $this->assertArrayNotHasKey('sumber_keluar', $record);
    }

    public function test_list_kosong_mengembalikan_collection_kosong(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/presensi-gate')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Daftar presensi gate berhasil diambil.',
                'data' => [
                    'presensi_gate' => [],
                ],
            ]);
    }

    public function test_admin_dan_operator_dapat_membaca_detail_presensi_gate(): void
    {
        $gate = $this->buatPresensiGate('2026-10-06');

        foreach (['ADMIN', 'OPERATOR'] as $roleCode) {
            $pengguna = $this->buatPenggunaDenganRole($roleCode);

            $this->actingAs($pengguna, 'sanctum')
                ->getJson("/api/presensi-gate/{$gate->id_presensi_gate}")
                ->assertOk()
                ->assertJsonPath(
                    'data.presensi_gate.id_presensi_gate',
                    $gate->id_presensi_gate
                )
                ->assertJsonMissingPath('data.presensi_gate.status')
                ->assertJsonMissingPath('data.presensi_gate.waktu_keluar')
                ->assertJsonMissingPath('data.presensi_gate.sumber_keluar');
        }
    }

    public function test_detail_presensi_gate_yang_tidak_ada_menghasilkan_404(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/presensi-gate/999999')
            ->assertNotFound();
    }

    public function test_endpoint_read_tidak_memanggil_attendance_evaluation_service(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $gate = $this->buatPresensiGate('2026-10-06');
        $evaluator = Mockery::mock(AttendanceEvaluationService::class);
        $evaluator->shouldNotReceive('evaluate');
        $this->app->instance(AttendanceEvaluationService::class, $evaluator);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/presensi-gate')
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/presensi-gate/{$gate->id_presensi_gate}")
            ->assertOk();
    }

    private function buatPresensiGate(string $tanggal): PresensiGate
    {
        $siswa = Siswa::query()->firstOrFail();

        return PresensiGate::query()->create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => $tanggal,
            'waktu_masuk' => $tanggal . ' 06:46:13',
            'sumber_masuk' => 'RFID',
            'keterangan' => null,
            'dibuat_oleh' => null,
            'diubah_oleh' => null,
        ]);
    }

    private function buatPenggunaDenganRole(string $roleCode): Pengguna
    {
        $pengguna = Pengguna::query()->create([
            'username' => strtolower($roleCode) . '-gate-read-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $roleCode,
            'status' => 'AKTIF',
        ]);

        $role = Role::query()->where('kode_role', $roleCode)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }
}
