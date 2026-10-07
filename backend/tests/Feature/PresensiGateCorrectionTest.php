<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Pengguna;
use App\Models\PresensiGate;
use App\Models\Role;
use App\Models\Siswa;
use App\Services\AuditLogService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiGateCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_koreksi_memerlukan_autentikasi(): void
    {
        $gate = $this->buatPresensiGate();

        $this->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
            'keterangan' => 'Koreksi evidence.',
        ])->assertStatus(401);
    }

    public function test_admin_dan_operator_dapat_mengoreksi_presensi_gate(): void
    {
        $gate = $this->buatPresensiGate();

        foreach (['ADMIN', 'OPERATOR'] as $roleCode) {
            $actor = $this->buatPenggunaDenganRole($roleCode);

            $this->actingAs($actor)
                ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                    'keterangan' => "Koreksi oleh {$roleCode}.",
                ])
                ->assertOk();
        }
    }

    public function test_role_selain_admin_dan_operator_ditolak(): void
    {
        $gate = $this->buatPresensiGate();

        foreach (['KS', 'WAKA', 'WALI_KELAS', 'KAPROG', 'BK', 'GURU', 'SISWA'] as $roleCode) {
            $actor = $this->buatPenggunaDenganRole($roleCode);

            $this->actingAs($actor)
                ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                    'keterangan' => 'Tidak berwenang.',
                ])
                ->assertStatus(403);
        }
    }

    public function test_permission_koreksi_ditegakkan_terpisah_dari_role(): void
    {
        $gate = $this->buatPresensiGate();
        $operatorRole = Role::where('kode_role', 'OPERATOR')->firstOrFail();
        $operatorRole->permissions()->detach(
            \App\Models\Permission::where('kode_permission', 'presensi_gate.koreksi')
                ->value('id_permission')
        );
        $operator = $this->buatPenggunaDenganRole('OPERATOR');

        $this->actingAs($operator)
            ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                'keterangan' => 'Tanpa permission.',
            ])
            ->assertStatus(403);
    }

    public function test_record_yang_tidak_ada_menghasilkan_404(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin)
            ->putJson('/api/presensi-gate/999999', [
                'keterangan' => 'Tidak ada record.',
            ])
            ->assertNotFound();
    }

    public function test_payload_invalid_dan_koreksi_kosong_menghasilkan_422(): void
    {
        $gate = $this->buatPresensiGate();
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin)
            ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                'sumber_masuk' => 'TAP',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sumber_masuk']);

        $this->actingAs($admin)
            ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['koreksi']);
    }

    public function test_koreksi_mengubah_evidence_mencatat_audit_dan_mengabaikan_field_immutable(): void
    {
        $creator = $this->buatPenggunaDenganRole('ADMIN');
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $siswa = Siswa::firstOrFail();
        $gate = PresensiGate::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-10-06',
            'waktu_masuk' => '2026-10-06 06:46:13',
            'sumber_masuk' => 'RFID',
            'keterangan' => null,
            'dibuat_oleh' => $creator->id_pengguna,
            'diubah_oleh' => null,
        ]);

        $this->actingAs($admin)
            ->withHeaders(['User-Agent' => 'PresensiGateCorrectionTest/1.0'])
            ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                'waktu_masuk' => '2026-10-06 07:05:00',
                'sumber_masuk' => 'MANUAL',
                'keterangan' => 'Koreksi dari bukti gerbang.',
                'id_siswa' => $creator->id_pengguna,
                'tanggal' => '2026-10-07',
                'dibuat_oleh' => $admin->id_pengguna,
                'diubah_oleh' => $creator->id_pengguna,
            ])
            ->assertOk()
            ->assertJsonPath('data.id_siswa', $siswa->id_siswa)
            ->assertJsonPath('data.tanggal', '2026-10-06')
            ->assertJsonPath('data.sumber_masuk', 'MANUAL')
            ->assertJsonPath('data.diubah_oleh', $admin->id_pengguna);

        $gate->refresh();
        $this->assertSame($creator->id_pengguna, $gate->dibuat_oleh);
        $this->assertSame($siswa->id_siswa, $gate->id_siswa);
        $this->assertSame('2026-10-06', $gate->tanggal);
        $this->assertSame($admin->id_pengguna, $gate->diubah_oleh);

        $audit = AuditLog::where('nama_tabel', 'presensi_gate')
            ->where('id_data', (string) $gate->id_presensi_gate)
            ->where('aksi', 'KOREKSI')
            ->firstOrFail();

        $this->assertSame($admin->id_pengguna, $audit->id_pengguna);
        $this->assertSame('2026-10-06 06:46:13', $audit->data_sebelum['waktu_masuk']);
        $this->assertSame('RFID', $audit->data_sebelum['sumber_masuk']);
        $this->assertSame('2026-10-06 07:05:00', $audit->data_sesudah['waktu_masuk']);
        $this->assertSame('MANUAL', $audit->data_sesudah['sumber_masuk']);
        $this->assertSame('Koreksi dari bukti gerbang.', $audit->alasan);
        $this->assertNotNull($audit->alamat_ip);
        $this->assertSame('PresensiGateCorrectionTest/1.0', $audit->user_agent);
        $this->assertNotNull($audit->created_at);
    }

    public function test_kegagalan_audit_membatalkan_koreksi(): void
    {
        $gate = $this->buatPresensiGate();
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->app->instance(
            AuditLogService::class,
            new class extends AuditLogService {
                public function __construct() {}

                public function catat(
                    \App\Models\Pengguna $pengguna,
                    string $aksi,
                    string $namaTabel,
                    string|int $idData,
                    ?array $dataSebelum = null,
                    ?array $dataSesudah = null,
                    ?string $alasan = null
                ): AuditLog {
                    throw new Exception('Simulasi kegagalan audit.');
                }
            }
        );

        $this->actingAs($admin)
            ->putJson("/api/presensi-gate/{$gate->id_presensi_gate}", [
                'keterangan' => 'Perubahan yang harus rollback.',
            ])
            ->assertStatus(500);

        $this->assertDatabaseHas('presensi_gate', [
            'id_presensi_gate' => $gate->id_presensi_gate,
            'keterangan' => null,
            'diubah_oleh' => null,
        ]);
        $this->assertDatabaseMissing('audit_log', [
            'nama_tabel' => 'presensi_gate',
            'id_data' => (string) $gate->id_presensi_gate,
        ]);
    }

    public function test_tidak_ada_endpoint_delete_dan_record_tidak_dihapus(): void
    {
        $gate = $this->buatPresensiGate();
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $this->actingAs($admin)
            ->deleteJson("/api/presensi-gate/{$gate->id_presensi_gate}")
            ->assertStatus(405);

        $this->assertDatabaseHas('presensi_gate', [
            'id_presensi_gate' => $gate->id_presensi_gate,
        ]);
    }

    private function buatPresensiGate(): PresensiGate
    {
        $siswa = Siswa::firstOrFail();

        return PresensiGate::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-10-06',
            'waktu_masuk' => '2026-10-06 06:46:13',
            'sumber_masuk' => 'RFID',
            'keterangan' => null,
            'dibuat_oleh' => null,
            'diubah_oleh' => null,
        ]);
    }

    private function buatPenggunaDenganRole(string $roleCode): Pengguna
    {
        $pengguna = Pengguna::create([
            'username' => strtolower($roleCode) . '-gate-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $roleCode,
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $role = Role::where('kode_role', $roleCode)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }
}
