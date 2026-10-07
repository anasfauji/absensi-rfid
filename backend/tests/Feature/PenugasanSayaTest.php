<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Penugasan;
use App\Models\PenugasanKelas;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenugasanSayaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_wali_kelas_mendapat_penugasan_miliknya_pada_tanggal_efektif(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $penugasan = $this->buatPenugasan(
            $wali,
            $kelas,
            '2026-10-01',
            '2026-10-07'
        );

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=2026-10-07');

        $response->assertOk()
            ->assertJsonPath('data.penugasan.0.id_penugasan', $penugasan->id_penugasan)
            ->assertJsonPath('data.penugasan.0.status', 'AKTIF')
            ->assertJsonPath('data.penugasan.0.kelas.0.id_kelas', $kelas->id_kelas)
            ->assertJsonPath('data.penugasan.0.kelas.0.nama_kelas', $kelas->nama_kelas);
    }

    public function test_wali_kelas_hanya_mendapat_penugasannya_sendiri(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $waliLain = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $milikWali = $this->buatPenugasan($wali, $kelas, '2026-07-01');
        $this->buatPenugasan($waliLain, $kelas, '2026-07-01');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=2026-10-07');

        $response->assertOk()
            ->assertJsonCount(1, 'data.penugasan')
            ->assertJsonPath('data.penugasan.0.id_penugasan', $milikWali->id_penugasan);
    }

    public function test_penugasan_dengan_status_bukan_aktif_tidak_dikembalikan(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasan($wali, $kelas, '2026-07-01', null, 'SELESAI');

        $this->assertEmpty($this->ambilPenugasan($wali));
    }

    public function test_penugasan_yang_belum_mulai_tidak_dikembalikan(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasan($wali, $kelas, '2026-10-08');

        $this->assertEmpty($this->ambilPenugasan($wali));
    }

    public function test_penugasan_yang_sudah_selesai_tidak_dikembalikan(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasan($wali, $kelas, '2026-07-01', '2026-10-06');

        $this->assertEmpty($this->ambilPenugasan($wali));
    }

    public function test_wali_kelas_tanpa_penugasan_aktif_mendapat_collection_kosong(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=2026-10-07');

        $response->assertOk()
            ->assertExactJson([
                'data' => [
                    'penugasan' => [],
                ],
            ]);
    }

    public function test_role_selain_wali_kelas_mendapat_403(): void
    {
        $pengguna = $this->buatPenggunaDenganRole('SISWA');

        $this->actingAs($pengguna, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=2026-10-07')
            ->assertForbidden();
    }

    public function test_request_tanpa_autentikasi_mendapat_401(): void
    {
        $this->getJson('/api/penugasan-saya?tanggal=2026-10-07')
            ->assertUnauthorized();
    }

    public function test_format_tanggal_tidak_valid_mendapat_422(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=07-10-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal']);
    }

    private function ambilPenugasan(Pengguna $wali): array
    {
        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/penugasan-saya?tanggal=2026-10-07');

        $response->assertOk();

        return $response->json('data.penugasan');
    }

    private function buatPenggunaDenganRole(string $kodeRole): Pengguna
    {
        $pengguna = Pengguna::query()->create([
            'username' => strtolower($kodeRole) . '-penugasan-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $kodeRole,
            'status' => 'AKTIF',
        ]);

        $role = Role::query()->where('kode_role', $kodeRole)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }

    private function buatPenugasan(
        Pengguna $wali,
        Kelas $kelas,
        string $tanggalMulai,
        ?string $tanggalSelesai = null,
        string $status = 'AKTIF'
    ): Penugasan {
        $roleWali = Role::query()->where('kode_role', 'WALI_KELAS')->firstOrFail();
        $penugasan = Penugasan::query()->create([
            'id_pengguna' => $wali->id_pengguna,
            'id_role' => $roleWali->id_role,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'status' => $status,
        ]);

        PenugasanKelas::query()->create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $kelas->id_kelas,
        ]);

        return $penugasan;
    }
}
