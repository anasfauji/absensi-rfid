<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\Penugasan;
use App\Models\PenugasanKelas;
use App\Models\Role;
use App\Models\SesiPresensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesiPresensiIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_wali_kelas_melihat_sesi_dari_kelas_tanggung_jawab_aktif(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-07-01');
        $sesi = $this->buatSesi($kelas, '2026-10-07');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi');

        $response->assertOk()
            ->assertJsonCount(1, 'data.sesi_presensi')
            ->assertJsonPath('data.sesi_presensi.0.id_sesi_presensi', $sesi->id_sesi_presensi);
    }

    public function test_wali_kelas_tidak_melihat_sesi_kelas_lain(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelasTanggungJawab = Kelas::query()->firstOrFail();
        $kelasLain = Kelas::query()
            ->where('id_kelas', '!=', $kelasTanggungJawab->id_kelas)
            ->firstOrFail();
        $this->buatPenugasanWali($wali, $kelasTanggungJawab, '2026-07-01');
        $sesiLain = $this->buatSesi($kelasLain, '2026-10-07');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi');

        $response->assertOk()
            ->assertExactJson([
                'message' => 'Daftar sesi presensi berhasil diambil.',
                'data' => [
                    'sesi_presensi' => [],
                ],
            ]);
        $this->assertNotContains(
            $sesiLain->id_sesi_presensi,
            $response->json('data.sesi_presensi')
        );
    }

    public function test_penugasan_yang_belum_mulai_tidak_memberi_scope_sesi(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-10-08');
        $this->buatSesi($kelas, '2026-10-07');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertJsonPath('data.sesi_presensi', []);
    }

    public function test_penugasan_yang_sudah_selesai_tidak_memberi_scope_sesi(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-07-01', '2026-10-06');
        $this->buatSesi($kelas, '2026-10-07');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertJsonPath('data.sesi_presensi', []);
    }

    public function test_penugasan_bukan_aktif_tidak_memberi_scope_sesi(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-07-01', null, 'SELESAI');
        $this->buatSesi($kelas, '2026-10-07');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertJsonPath('data.sesi_presensi', []);
    }

    public function test_wali_kelas_tanpa_sesi_cocok_mendapat_collection_kosong(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-07-01');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Daftar sesi presensi berhasil diambil.',
                'data' => [
                    'sesi_presensi' => [],
                ],
            ]);
    }

    public function test_wali_kelas_mendapat_sesi_dari_seluruh_kelas_penugasan_aktif(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelasPertama = Kelas::query()->firstOrFail();
        $kelasKedua = Kelas::query()
            ->where('id_kelas', '!=', $kelasPertama->id_kelas)
            ->firstOrFail();
        $this->buatPenugasanWali($wali, $kelasPertama, '2026-07-01');
        $this->buatPenugasanWali($wali, $kelasKedua, '2026-07-01');
        $sesiPertama = $this->buatSesi($kelasPertama, '2026-10-07');
        $sesiKedua = $this->buatSesi($kelasKedua, '2026-10-07', '08:00:00');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi');

        $response->assertOk()
            ->assertJsonCount(2, 'data.sesi_presensi');
        $this->assertEqualsCanonicalizing(
            [$sesiPertama->id_sesi_presensi, $sesiKedua->id_sesi_presensi],
            collect($response->json('data.sesi_presensi'))
                ->pluck('id_sesi_presensi')
                ->all()
        );
    }

    public function test_sesi_di_luar_rentang_efektif_penugasan_tidak_dikembalikan(): void
    {
        $wali = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelas = Kelas::query()->firstOrFail();
        $this->buatPenugasanWali($wali, $kelas, '2026-10-05', '2026-10-10');
        $sesiSebelum = $this->buatSesi($kelas, '2026-10-04');
        $sesiEfektif = $this->buatSesi($kelas, '2026-10-07', '08:00:00');
        $sesiSesudah = $this->buatSesi($kelas, '2026-10-11');

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson('/api/sesi-presensi');

        $response->assertOk()
            ->assertJsonCount(1, 'data.sesi_presensi')
            ->assertJsonPath('data.sesi_presensi.0.id_sesi_presensi', $sesiEfektif->id_sesi_presensi);
        $returnedIds = collect($response->json('data.sesi_presensi'))
            ->pluck('id_sesi_presensi')
            ->all();
        $this->assertNotContains($sesiSebelum->id_sesi_presensi, $returnedIds);
        $this->assertNotContains($sesiSesudah->id_sesi_presensi, $returnedIds);
    }

    public function test_admin_tetap_melihat_seluruh_sesi(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');
        $kelasPertama = Kelas::query()->firstOrFail();
        $kelasKedua = Kelas::query()
            ->where('id_kelas', '!=', $kelasPertama->id_kelas)
            ->firstOrFail();
        $this->buatSesi($kelasPertama, '2026-10-07');
        $this->buatSesi($kelasKedua, '2026-10-07');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertJsonCount(2, 'data.sesi_presensi');
    }

    public function test_operator_tetap_melihat_seluruh_sesi(): void
    {
        $operator = $this->buatPenggunaDenganRole('OPERATOR');
        $kelasPertama = Kelas::query()->firstOrFail();
        $kelasKedua = Kelas::query()
            ->where('id_kelas', '!=', $kelasPertama->id_kelas)
            ->firstOrFail();
        $this->buatSesi($kelasPertama, '2026-10-07');
        $this->buatSesi($kelasKedua, '2026-10-07');

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertOk()
            ->assertJsonCount(2, 'data.sesi_presensi');
    }

    public function test_guru_tetap_mendapat_sesi_pemilik_dan_penangan_saja(): void
    {
        $guru = $this->buatGuru();
        $guruLain = $this->buatGuru();
        $guruDiLuarScope = $this->buatGuru();
        $penggunaGuru = $this->buatPenggunaDenganRole('GURU', $guru->id_guru);
        $kelas = Kelas::query()->firstOrFail();
        $sesiPemilik = $this->buatSesi($kelas, '2026-10-07', '07:00:00', $guru->id_guru);
        $sesiPenangan = $this->buatSesi(
            $kelas,
            '2026-10-07',
            '08:00:00',
            $guruLain->id_guru,
            $guru->id_guru
        );
        $this->buatSesi($kelas, '2026-10-07', '09:00:00', $guruDiLuarScope->id_guru);

        $response = $this->actingAs($penggunaGuru, 'sanctum')
            ->getJson('/api/sesi-presensi');

        $response->assertOk()
            ->assertJsonCount(2, 'data.sesi_presensi');
        $this->assertEqualsCanonicalizing(
            [$sesiPemilik->id_sesi_presensi, $sesiPenangan->id_sesi_presensi],
            collect($response->json('data.sesi_presensi'))
                ->pluck('id_sesi_presensi')
                ->all()
        );
    }

    public function test_role_lain_tetap_ditolak(): void
    {
        $pengguna = $this->buatPenggunaDenganRole('KS');

        $this->actingAs($pengguna, 'sanctum')
            ->getJson('/api/sesi-presensi')
            ->assertForbidden();
    }

    public function test_request_tanpa_autentikasi_mendapat_401(): void
    {
        $this->getJson('/api/sesi-presensi')
            ->assertUnauthorized();
    }

    private function buatPenggunaDenganRole(string $kodeRole, ?int $idGuru = null): Pengguna
    {
        $pengguna = Pengguna::query()->create([
            'username' => strtolower($kodeRole) . '-sesi-index-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $kodeRole,
            'id_guru' => $idGuru,
            'status' => 'AKTIF',
        ]);

        $role = Role::query()->where('kode_role', $kodeRole)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }

    private function buatPenugasanWali(
        Pengguna $wali,
        Kelas $kelas,
        string $tanggalMulai,
        ?string $tanggalSelesai = null,
        string $status = 'AKTIF'
    ): Penugasan {
        $role = Role::query()->where('kode_role', 'WALI_KELAS')->firstOrFail();
        $penugasan = Penugasan::query()->create([
            'id_pengguna' => $wali->id_pengguna,
            'id_role' => $role->id_role,
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

    private function buatSesi(
        Kelas $kelas,
        string $tanggal,
        string $waktuMulai = '07:00:00',
        ?int $idGuru = null,
        ?int $idGuruPenangan = null
    ): SesiPresensi {
        $guru = $idGuru === null
            ? Guru::query()->where('status', 'AKTIF')->firstOrFail()
            : Guru::query()->findOrFail($idGuru);
        $mataPelajaran = MataPelajaran::query()
            ->where('kode_mata_pelajaran', 'RPL-DASAR')
            ->firstOrFail();

        return SesiPresensi::query()->create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => $idGuruPenangan,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test daftar sesi presensi',
        ]);
    }

    private function buatGuru(): Guru
    {
        return Guru::query()->create([
            'nip' => 'INDEX-' . strtoupper(substr(uniqid(), -12)),
            'nama_guru' => 'Guru test daftar sesi',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);
    }
}
