<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Kalender;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SesiPresensi;
use App\Models\PresensiKelas;
use App\Models\TahunAjaran;
use App\Models\PenempatanSiswa;
use App\Models\Kegiatan;
use App\Models\Penugasan;
use App\Models\Jurusan;
use App\Models\PenugasanJurusan;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EvaluasiPresensiRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_request_menerima_data_yang_valid(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();


        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }


    public function test_request_menolak_tanggal_yang_tidak_valid(): void
    {

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=23-09-2026'
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'tanggal',
            ]);
    }

    public function test_request_menolak_tanggal_yang_tidak_diisi(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi'
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'tanggal',
            ]);
    }

    public function test_request_menolak_id_siswa_yang_tidak_terdaftar(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/999999/evaluasi-presensi'
                    . '?tanggal=2026-09-23'
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_siswa',
            ]);
    }

    public function test_request_menolak_id_siswa_yang_bukan_integer(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/abc/evaluasi-presensi'
                    . '?tanggal=2026-09-23'
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_siswa',
            ]);
    }

    public function test_siswa_tidak_boleh_melihat_evaluasi_siswa_lain(): void
    {
        $siswa1 = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $siswa2 = Siswa::where(
            'nis',
            '260002'
        )->firstOrFail();

        $penggunaSiswa1 = Pengguna::where(
            'id_siswa',
            $siswa1->id_siswa
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaSiswa1, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa2->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertForbidden();
    }

    public function test_siswa_boleh_melihat_evaluasi_dirinya_sendiri(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penggunaSiswa = Pengguna::where(
            'id_siswa',
            $siswa->id_siswa
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaSiswa, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }


    public function test_admin_boleh_melihat_evaluasi_siswa_mana_pun(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260002'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }

    public function test_kaprog_boleh_melihat_siswa_dari_jurusannya(): void
    {
        $penggunaKaproG = $this->buatPenggunaKaproG();

        $this->buatPenugasanKaproG(
            $penggunaKaproG
        );

        $siswaRpl = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaKaproG, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswaRpl->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }

    public function test_kaprog_tidak_boleh_melihat_siswa_dari_jurusan_lain(): void
    {
        $penggunaKaproG = $this->buatPenggunaKaproG();

        $this->buatPenugasanKaproG(
            $penggunaKaproG
        );

        $siswaTkr = $this->buatSiswaJurusanTkr();

        $response = $this
            ->actingAs($penggunaKaproG, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswaTkr->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertForbidden();
    }

    public function test_endpoint_mengembalikan_hadir_jika_ada_presensi_rfid(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = \App\Models\TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        \App\Models\PresensiGate::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-09-23',
            'waktu_masuk' => '2026-09-23 06:55:00',
            'sumber_masuk' => 'RFID',
            'keterangan' => 'Fixture test RFID.',
            'dibuat_oleh' => $admin->id_pengguna,
            'diubah_oleh' => null,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'HADIR',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_wali_kelas_boleh_melihat_siswa_dari_kelas_tugasnya(): void
    {
        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();
        $guru = Guru::firstOrFail();

        $waliKelas = Pengguna::create([
            'username' => 'wali_kelas_test',
            'password' => Hash::make('WaliKelas123!'),
            'nama_tampilan' => $guru->nama_guru,
            'email' => null,
            'id_guru' => $guru->id_guru,
            'status' => 'AKTIF',
        ]);

        $roleWaliKelas = Role::where('kode_role', 'WALI_KELAS')
            ->firstOrFail();

        $waliKelas->roles()->sync([
            $roleWaliKelas->id_role,
        ]);

        $penempatan = PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $penugasan = Penugasan::create([
            'id_pengguna' => $waliKelas->id_pengguna,
            'id_role' => Role::where('kode_role', 'WALI_KELAS')
                ->firstOrFail()
                ->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test wali kelas.',
        ]);

        DB::table('penugasan_kelas')->insert([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $penempatan->id_kelas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($waliKelas, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk();
    }

    public function test_wali_kelas_tidak_boleh_melihat_siswa_dari_kelas_lain(): void
    {
        $siswa = Siswa::where('nis', '260002')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        $guru = Guru::firstOrFail();

        $waliKelas = Pengguna::create([
            'username' => 'wali_kelas_test',
            'password' => Hash::make('WaliKelas123!'),
            'nama_tampilan' => $guru->nama_guru,
            'email' => null,
            'id_guru' => $guru->id_guru,
            'status' => 'AKTIF',
        ]);

        $roleWaliKelas = Role::where('kode_role', 'WALI_KELAS')
            ->firstOrFail();

        $waliKelas->roles()->sync([
            $roleWaliKelas->id_role,
        ]);

        $penempatan = PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $penugasan = Penugasan::create([
            'id_pengguna' => $waliKelas->id_pengguna,
            'id_role' => $roleWaliKelas->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test wali kelas.',
        ]);

        $kelasYangDitugaskan = Kelas::where(
            'id_kelas',
            '!=',
            $penempatan->id_kelas
        )->firstOrFail();

        DB::table('penugasan_kelas')->insert([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $kelasYangDitugaskan->id_kelas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($waliKelas, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertForbidden();
    }


    public function test_guru_boleh_melihat_siswa_dari_sesi_presensinya(): void
    {
        $guru = Guru::firstOrFail();

        $roleGuru = Role::where('kode_role', 'GURU')
            ->firstOrFail();

        $penggunaGuru = Pengguna::create([
            'username' => 'guru_endpoint_test_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $penggunaGuru->roles()->attach(
            $roleGuru->id_role
        );

        $siswa = Siswa::where('nis', '260001')
            ->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $mataPelajaran = MataPelajaran::firstOrFail();

        SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $penempatan->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Test endpoint GURU',
            'keterangan' => 'Fixture test scope GURU.',
        ]);

        $response = $this->actingAs(
            $penggunaGuru,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertOk();
    }


    public function test_guru_tidak_boleh_melihat_siswa_dari_kelas_lain(): void
    {
        $guru = Guru::firstOrFail();

        $roleGuru = Role::where('kode_role', 'GURU')
            ->firstOrFail();

        $penggunaGuru = Pengguna::create([
            'username' => 'guru_endpoint_other_class_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $penggunaGuru->roles()->attach(
            $roleGuru->id_role
        );

        $siswa = Siswa::where('nis', '260002')
            ->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $mataPelajaran = MataPelajaran::firstOrFail();

        // Buat sesi untuk kelas siswa 260001,
        // bukan kelas siswa 260002.
        $siswaLain = Siswa::where('nis', '260001')
            ->firstOrFail();

        $penempatanSiswaLain = PenempatanSiswa::where(
            'id_siswa',
            $siswaLain->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $penempatanSiswaLain->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Test endpoint GURU',
            'keterangan' => 'Fixture test scope GURU.',
        ]);

        $response = $this->actingAs(
            $penggunaGuru,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertForbidden();
    }


    public function test_guru_tidak_boleh_melihat_sesi_milik_guru_lain(): void
    {
        $guruA = Guru::firstOrFail();

        $guruB = Guru::create([
            'nip' => 'TEST-GURU-B-' . uniqid(),
            'nama_guru' => 'Guru B Test',
            'jenis_kelamin' => 'P',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        // Pengguna Guru A
        $penggunaGuruA = Pengguna::create([
            'username' => 'guru_a_endpoint_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruA->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruA->id_guru,
        ]);

        $penggunaGuruA->roles()->attach(
            $roleGuru->id_role
        );

        // Siswa yang akan dilihat
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $mataPelajaran = MataPelajaran::firstOrFail();

        // Sesi dibuat oleh Guru B
        SesiPresensi::create([
            'id_guru' => $guruB->id_guru,
            'id_kelas' => $penempatan->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Test endpoint Guru B',
            'keterangan' => 'Fixture sesi milik guru lain.',
        ]);

        // Guru A mencoba melihat evaluasi siswa
        $response = $this->actingAs(
            $penggunaGuruA,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertForbidden();
    }


    public function test_guru_tidak_boleh_melihat_sesi_presensi_pada_tanggal_lain(): void
    {
        $guru = Guru::firstOrFail();

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuru = Pengguna::create([
            'username' => 'guru_endpoint_tanggal_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $penggunaGuru->roles()->attach(
            $roleGuru->id_role
        );

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $mataPelajaran = MataPelajaran::firstOrFail();

        // Guru memiliki sesi pada 23 September.
        SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $penempatan->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Test endpoint GURU',
            'keterangan' => 'Fixture sesi tanggal 23 September.',
        ]);

        // Tetapi Guru meminta evaluasi tanggal 24 September.
        $response = $this->actingAs(
            $penggunaGuru,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-24'
        );

        $response->assertForbidden();
    }


    public function test_guru_tanpa_id_guru_tidak_boleh_melihat_evaluasi(): void
    {
        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuru = Pengguna::create([
            'username' => 'guru_tanpa_id_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Tanpa ID',
            'status' => 'AKTIF',
            'id_guru' => null,
        ]);

        $penggunaGuru->roles()->attach(
            $roleGuru->id_role
        );

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $response = $this->actingAs(
            $penggunaGuru,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertForbidden();
    }


    public function test_multi_role_guru_dan_kaprog_dapat_melihat_melalui_scope_kaprog(): void
    {
        $guru = Guru::firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru_kaprog_endpoint_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $roleKaproG = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $pengguna->roles()->attach([
            $roleGuru->id_role,
            $roleKaproG->id_role,
        ]);

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $jurusan = Jurusan::findOrFail(
            $penempatan->kelas->id_jurusan
        );

        $rolePenugasan = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $rolePenugasan->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Test multi-role KAPROG.',
        ]);

        PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $jurusan->id_jurusan,
        ]);

        $response = $this->actingAs(
            $pengguna,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertOk();
    }


    public function test_multi_role_guru_dan_kaprog_tidak_boleh_melihat_jurusan_lain(): void
    {
        $guru = Guru::firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru_kaprog_negatif_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $roleKaproG = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $pengguna->roles()->attach([
            $roleGuru->id_role,
            $roleKaproG->id_role,
        ]);

        $jurusanRpl = Jurusan::where(
            'kode_jurusan',
            'RPL'
        )->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $roleKaproG->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Test multi-role KAPROG negatif.',
        ]);

        PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $jurusanRpl->id_jurusan,
        ]);

        // Siswa berasal dari jurusan TKR.
        $tahunAjaran = TahunAjaran::where(
            'nama_tahun_ajaran',
            '2026/2027'
        )->firstOrFail();

        $jurusanTkr = Jurusan::where(
            'kode_jurusan',
            'TKR'
        )->firstOrFail();

        $kelasTkr = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusanTkr->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X TKR MULTI ROLE TEST',
            'status' => 'AKTIF',
        ]);

        $siswaTkr = Siswa::create([
            'nis' => 'TEST-TKR-MULTI-' . uniqid(),
            'nama_siswa' => 'Siswa TKR Multi Role Test',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswaTkr->id_siswa,
            'id_kelas' => $kelasTkr->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test multi-role negatif.',
        ]);

        $response = $this->actingAs(
            $pengguna,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswaTkr->id_siswa .
                '/evaluasi-presensi?tanggal=2026-09-23'
        );

        $response->assertForbidden();
    }


    public function test_multi_role_kaprog_tidak_boleh_melihat_setelah_penugasan_berakhir(): void
    {
        $guru = Guru::firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru_kaprog_temporal_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $roleKaproG = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $pengguna->roles()->attach([
            $roleGuru->id_role,
            $roleKaproG->id_role,
        ]);

        $jurusanRpl = Jurusan::where(
            'kode_jurusan',
            'RPL'
        )->firstOrFail();

        // Penugasan KAPROG hanya berlaku sampai 30 September 2026.
        $penugasan = Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $roleKaproG->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => 'Test temporal multi-role KAPROG.',
        ]);

        PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $jurusanRpl->id_jurusan,
        ]);

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        // Tidak membuat SesiPresensi.
        // Jadi akses hanya mungkin berasal dari KAPROG.
        $response = $this->actingAs(
            $pengguna,
            'sanctum'
        )->getJson(
            '/api/siswa/' .
                $siswa->id_siswa .
                '/evaluasi-presensi?tanggal=2026-10-01'
        );

        $response->assertForbidden();
    }


    public function test_endpoint_mengembalikan_hadir_jika_ada_presensi_kelas(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail(
            $penempatan->id_kelas
        );

        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test presensi kelas.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '2026-09-23 08:05:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test presensi kelas.',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'HADIR',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_hadir_jika_presensi_kelas_terlambat(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail(
            $penempatan->id_kelas
        );

        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test presensi kelas terlambat.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'TERLAMBAT',
            'waktu_presensi' => '2026-09-23 08:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test presensi terlambat.',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'HADIR',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_sakit_jika_presensi_kelas_sakit(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail(
            $penempatan->id_kelas
        );

        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test presensi kelas terlambat.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'SAKIT',
            'waktu_presensi' => '2026-09-23 08:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test presensi sakit.',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'SAKIT',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_izin_jika_presensi_kelas_izin(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail(
            $penempatan->id_kelas
        );

        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test presensi kelas izin.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'IZIN',
            'waktu_presensi' => '2026-09-23 08:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test presensi izin.',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'IZIN',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_dispensasi_jika_presensi_kelas_dispensasi(): void
    {
        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $tahunAjaran = TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail(
            $penempatan->id_kelas
        );

        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test presensi dispensasi.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'DISPENSASI',
            'waktu_presensi' => '2026-09-23 08:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test presensi dispensasi.',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/'
                    . $siswa->id_siswa
                    . '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'DISPENSASI',
                    'wajib_hadir' => true,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_alpa_setelah_cutoff(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 23:59:59')
        );

        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'ALPA',
                    'wajib_hadir' => true,
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_endpoint_belum_mengembalikan_alpa_sebelum_cutoff(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 23:59:58')
        );

        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => null,
                    'wajib_hadir' => true,
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_endpoint_mengembalikan_tidak_berlaku_jika_hari_tidak_aktif(): void
    {
        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'TIDAK_AKTIF',
            'keterangan' => 'Fixture test hari tidak aktif.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_tidak_berlaku_jika_siswa_sedang_pkl(): void
    {
        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test kegiatan PKL aktif.',
        ]);

        DB::table('kegiatan_siswa')->insert([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_siswa' => $siswa->id_siswa,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'TIDAK_BERLAKU',
                    'wajib_hadir' => false,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_data_tidak_lengkap_jika_penempatan_siswa_tidak_ditemukan(): void
    {
        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->update([
                'tanggal_mulai' => '2026-09-24',
            ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'DATA_TIDAK_LENGKAP',
                    'wajib_hadir' => null,
                ],
            ]);
    }

    public function test_endpoint_mengembalikan_null_jika_terdapat_mixed_nonattendance(): void
    {
        $siswa = Siswa::where('nis', '260001')->firstOrFail();
        $admin = Pengguna::where('username', 'admin')->firstOrFail();
        $tahunAjaran = TahunAjaran::where('status', 'AKTIF')->firstOrFail();

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => 'Fixture test hari sekolah aktif.',
        ]);

        $guru = Guru::firstOrFail();

        $penempatan = PenempatanSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = Kelas::findOrFail($penempatan->id_kelas);
        $mataPelajaran = MataPelajaran::firstOrFail();

        $guruKedua = Guru::create([
            'nip' => 'MIXED-GURU-' . uniqid(),
            'nama_guru' => 'Guru Mixed Nonattendance',
            'jenis_kelamin' => 'P',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $sesiSakit = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test sesi sakit.',
            'keterangan' => null,
        ]);

        $sesiIzin = SesiPresensi::create([
            'id_guru' => $guruKedua->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '11:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Fixture test sesi izin.',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiSakit->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'SAKIT',
            'waktu_presensi' => '2026-09-23 08:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test sakit.',
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiIzin->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'IZIN',
            'waktu_presensi' => '2026-09-23 10:30:00',
            'sumber' => 'GURU',
            'keterangan' => 'Fixture test izin.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/siswa/' .
                    $siswa->id_siswa .
                    '/evaluasi-presensi?tanggal=2026-09-23'
            );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => null,
                    'wajib_hadir' => true,
                ],
            ]);
    }


    //=============Helper============
    //1. KAPROG
    private function buatPenggunaKaproG(): Pengguna
    {
        $guru = \App\Models\Guru::firstOrFail();

        $roleKaproG = \App\Models\Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'kaprog_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $pengguna->roles()->attach(
            $roleKaproG->id_role
        );

        return $pengguna;
    }

    private function buatPenugasanKaproG(
        Pengguna $pengguna
    ): void {
        $roleKaproG = \App\Models\Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $jurusanRpl = \App\Models\Jurusan::where(
            'kode_jurusan',
            'RPL'
        )->firstOrFail();

        $penugasan = \App\Models\Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $roleKaproG->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'KaproG RPL untuk pengujian endpoint.',
        ]);

        \App\Models\PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $jurusanRpl->id_jurusan,
        ]);
    }

    //2. Siswa TKR
    private function buatSiswaJurusanTkr(): Siswa
    {
        $jurusanTkr = \App\Models\Jurusan::where(
            'kode_jurusan',
            'TKR'
        )->firstOrFail();

        $tahunAjaran = \App\Models\TahunAjaran::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelasTkr = \App\Models\Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusanTkr->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X TKR Test',
            'status' => 'AKTIF',
        ]);

        $siswa = \App\Models\Siswa::create([
            'nis' => 'TEST-TKR-001',
            'nama_siswa' => 'Siswa Test TKR',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        \App\Models\PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTkr->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test scope KAPROG.',
        ]);

        return $siswa;
    }
}
