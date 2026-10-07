<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SesiPresensi;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\Jurusan;
use Laravel\Sanctum\Sanctum;
use App\Models\PresensiKelas;
use App\Models\Role;
use App\Models\AuditLog;
use App\Models\Penugasan;
use App\Models\PenugasanKelas;
use App\Models\Permission;
use App\Services\AuditLogService;
use Exception;


class PresensiKelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_status_presensi_kelas_wajib_diisi(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b133',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B13.3',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/1/presensi/1',
                [
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'status',
        ]);
    }

    public function test_status_presensi_kelas_harus_valid(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b134',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B13.4',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/1/presensi/1',
                [
                    'status' => 'HADIRR',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'status',
        ]);
    }

    public function test_waktu_presensi_harus_berformat_hh_mm_ss(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b135',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B13.5',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/1/presensi/1',
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:5',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'waktu_presensi',
        ]);
    }

    public function test_sumber_presensi_kelas_harus_valid(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b136',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B13.6',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/1/presensi/1',
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'RFID',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'sumber',
        ]);
    }

    public function test_keterangan_presensi_kelas_boleh_kosong(): void
    {
        $guruPemilik = Guru::where('status', 'AKTIF')->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B137-002',
            'nama_guru' => 'Guru Penangan B13.7',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b137',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B13.7',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B13.7',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/'
                    . $sesiPresensi->id_sesi_presensi
                    . '/presensi/'
                    . $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
                'id_siswa' => $siswa->id_siswa,
                'keterangan' => null,
            ],
        ]);
    }

    public function test_guru_penangan_tidak_boleh_mengelola_siswa_di_luar_kelas_sesi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B141-002',
            'nama_guru' => 'Guru Penangan B14.1',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b141',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B14.1',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST-B141',
            'npsn' => '99999999',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $kelasLain = Kelas::where(
            'nama_kelas',
            'X RPL 2'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.1',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasLain) {
                $query->where(
                    'id_kelas',
                    $kelasLain->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_guru_pemilik_tidak_boleh_mengelola_siswa_di_luar_kelas_sesi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::where(
            'id_guru',
            $guruPemilik->id_guru
        )->first();

        if (! $penggunaPemilik) {
            $penggunaPemilik = Pengguna::create([
                'username' => 'guru-pemilik-luar-kelas',
                'password' => bcrypt('password'),
                'nama_tampilan' => 'Guru Pemilik Luar Kelas',
                'email' => null,
                'foto' => null,
                'id_guru' => $guruPemilik->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $penggunaPemilik->roles()->attach(
                $roleGuru->id_role
            );
        }

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $kelasLain = Kelas::where(
            'nama_kelas',
            'X RPL 2'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Guru Pemilik',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasLain) {
                $query->where(
                    'id_kelas',
                    $kelasLain->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_guru_penangan_boleh_mengelola_siswa_di_kelas_sesi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B142-002',
            'nama_guru' => 'Guru Penangan B14.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b142',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B14.2',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.2',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
                'id_siswa' => $siswa->id_siswa,
            ],
        ]);
    }

    public function test_guru_pemilik_boleh_mengelola_siswa_di_kelas_sesi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::where(
            'id_guru',
            $guruPemilik->id_guru
        )->first();

        if (! $penggunaPemilik) {
            $penggunaPemilik = Pengguna::create([
                'username' => 'guru-b143',
                'password' => bcrypt('password'),
                'nama_tampilan' => 'Guru Pemilik B14.3',
                'email' => null,
                'foto' => null,
                'id_guru' => $guruPemilik->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $penggunaPemilik->roles()->attach(
                $roleGuru->id_role
            );
        }

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.3',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
                'id_siswa' => $siswa->id_siswa,
            ],
        ]);
    }


    public function test_guru_lain_tidak_boleh_mengelola_presensi_sesi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B144-002',
            'nama_guru' => 'Guru Penangan B14.4',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruLain = Guru::create([
            'nip' => 'TEST-B144-003',
            'nama_guru' => 'Guru Lain B14.4',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaLain = Pengguna::create([
            'username' => 'guru-b144',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Lain B14.4',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruLain->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.4',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaLain)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_siswa_tidak_boleh_mengelola_presensi_kelas(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $penggunaSiswa = Pengguna::where(
            'id_siswa',
            $siswa->id_siswa
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.5',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaSiswa)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_admin_boleh_mengelola_presensi_kelas(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.6',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'SISTEM',
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
                'id_siswa' => $siswa->id_siswa,
            ],
        ]);
    }

    public function test_operator_boleh_mengelola_presensi_kelas(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::create([
            'username' => 'operator-b147',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator B14.7',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $operator->roles()->attach(
            $roleOperator->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.7',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($operator)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'SISTEM',
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
                'id_siswa' => $siswa->id_siswa,
            ],
        ]);
    }

    public function test_wali_kelas_dapat_mengoreksi_siswa_di_kelas_tanggung_jawab_aktif(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
            [
                'status' => 'IZIN',
                'sumber' => 'WALI_KELAS',
                'keterangan' => 'Koreksi wali kelas.',
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('presensi_kelas', [
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'IZIN',
        ]);
    }

    public function test_wali_kelas_ditolak_untuk_kelas_lain(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelasLain = Kelas::query()
            ->where('id_kelas', '!=', $sesi->id_kelas)
            ->firstOrFail();
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $kelasLain->id_kelas,
            '2026-07-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
            [
                'status' => 'IZIN',
                'sumber' => 'WALI_KELAS',
            ]
        );

        $response->assertForbidden();
    }

    public function test_wali_kelas_ditolak_jika_penugasan_belum_berlaku_pada_tanggal_sesi(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-10-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
            [
                'status' => 'IZIN',
                'sumber' => 'WALI_KELAS',
            ]
        );

        $response->assertForbidden();
    }

    public function test_wali_kelas_ditolak_jika_penugasan_sudah_berakhir_pada_tanggal_sesi(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            '2026-09-29'
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
            [
                'status' => 'IZIN',
                'sumber' => 'WALI_KELAS',
            ]
        );

        $response->assertForbidden();
    }

    public function test_permission_koreksi_terpisah_dari_permission_lihat(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            null
        );

        $roleWaliKelas = Role::where('kode_role', 'WALI_KELAS')->firstOrFail();
        $permissionKoreksi = Permission::where(
            'kode_permission',
            'presensi_kelas.koreksi'
        )->firstOrFail();
        $roleWaliKelas->permissions()->detach($permissionKoreksi->id_permission);

        $response = $this->actingAs($waliKelas, 'sanctum')->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
            [
                'status' => 'IZIN',
                'sumber' => 'WALI_KELAS',
            ]
        );

        $response->assertForbidden();
    }

    public function test_role_tanpa_permission_koreksi_ditolak(): void
    {
        [$sesi, $siswa] = $this->buatSesiAktifDenganSiswa();

        foreach (['KS', 'WAKA', 'KAPROG', 'BK', 'SISWA'] as $roleCode) {
            $pengguna = $this->buatPenggunaDenganRole($roleCode);

            $response = $this->actingAs($pengguna, 'sanctum')->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'IZIN',
                    'sumber' => 'GURU',
                ]
            );

            $response->assertForbidden();
        }
    }

    public function test_wali_kelas_dapat_melihat_presensi_sesi_kelas_tanggung_jawab_aktif(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_wali_kelas_ditolak_melihat_presensi_sesi_kelas_lain(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $kelasLain = Kelas::query()
            ->where('id_kelas', '!=', $sesi->id_kelas)
            ->firstOrFail();
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $kelasLain->id_kelas,
            '2026-07-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertForbidden();
    }

    public function test_wali_kelas_ditolak_melihat_sesi_sebelum_penugasan_berlaku(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-10-01',
            null
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertForbidden();
    }

    public function test_wali_kelas_ditolak_melihat_sesi_setelah_penugasan_berakhir(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            '2026-09-29'
        );

        $response = $this->actingAs($waliKelas, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertForbidden();
    }

    public function test_wali_kelas_dapat_melihat_tanpa_permission_koreksi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');
        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $sesi->id_kelas,
            '2026-07-01',
            null
        );

        $roleWaliKelas = Role::where('kode_role', 'WALI_KELAS')->firstOrFail();
        $permissionKoreksi = Permission::where(
            'kode_permission',
            'presensi_kelas.koreksi'
        )->firstOrFail();
        $roleWaliKelas->permissions()->detach($permissionKoreksi->id_permission);

        $this->assertTrue($waliKelas->hasPermission('presensi_kelas.lihat'));
        $this->assertFalse($waliKelas->hasPermission('presensi_kelas.koreksi'));

        $response = $this->actingAs($waliKelas, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_admin_dapat_melihat_presensi_lintas_sesi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $response = $this->actingAs($admin, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_operator_dapat_melihat_presensi_lintas_sesi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $operator = $this->buatPenggunaDenganRole('OPERATOR');

        $response = $this->actingAs($operator, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_guru_penanggung_jawab_sesi_dapat_melihat_presensi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $guru = $this->buatPenggunaDenganRole('GURU', $sesi->id_guru);

        $response = $this->actingAs($guru, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_guru_penangan_sesi_dapat_melihat_presensi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $guruPenangan = Guru::create([
            'nip' => 'AUTH' . strtoupper(substr(uniqid(), -12)),
            'nama_guru' => 'Guru penangan authorization',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);
        $sesi->update(['id_guru_penangan' => $guruPenangan->id_guru]);
        $penggunaGuru = $this->buatPenggunaDenganRole(
            'GURU',
            $guruPenangan->id_guru
        );

        $response = $this->actingAs($penggunaGuru, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertOk();
    }

    public function test_guru_di_luar_scope_ditolak_melihat_presensi(): void
    {
        [$sesi] = $this->buatSesiAktifDenganSiswa();
        $guruLain = Guru::create([
            'nip' => 'AUTH' . strtoupper(substr(uniqid(), -12)),
            'nama_guru' => 'Guru di luar scope',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);
        $penggunaGuru = $this->buatPenggunaDenganRole(
            'GURU',
            $guruLain->id_guru
        );

        $response = $this->actingAs($penggunaGuru, 'sanctum')->getJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi"
        );

        $response->assertForbidden();
    }

    public function test_guru_penangan_dapat_membuat_presensi_kelas(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B148-002',
            'nama_guru' => 'Guru Penangan B14.8',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b148',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B14.8',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.8',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);
    }

    public function test_guru_penangan_dapat_mengubah_presensi_kelas_yang_sudah_ada(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B149-002',
            'nama_guru' => 'Guru Penangan B14.9',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b149',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B14.9',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi B14.9',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        // Presensi pertama
        $responsePertama = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $responsePertama->assertStatus(200);

        // Koreksi presensi
        $responseKedua = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'TERLAMBAT',
                    'waktu_presensi' => '07:15:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Koreksi waktu datang',
                ]
            );

        $responseKedua->assertStatus(200);

        $this->assertDatabaseHas('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'TERLAMBAT',
            'waktu_presensi' => '07:15:00',
            'sumber' => 'GURU',
            'keterangan' => 'Koreksi waktu datang',
        ]);

        $this->assertDatabaseCount('presensi_kelas', 1);
    }

    public function test_presensi_kelas_tidak_boleh_diubah_pada_sesi_draft(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B149-002',
            'nama_guru' => 'Guru Penangan B14.9',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b149',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B14.9',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelasSesi = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B14.9',
            'keterangan' => null,
        ]);

        $siswa = Siswa::whereHas(
            'penempatanSiswa',
            function ($query) use ($kelasSesi) {
                $query->where(
                    'id_kelas',
                    $kelasSesi->id_kelas
                );
            }
        )->firstOrFail();

        // Presensi pertama
        $responsePertama = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        // Percobaan mengisi presensi pada sesi DRAFT
        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_admin_tidak_boleh_mengelola_presensi_siswa_di_luar_kelas_sesi(): void
    {
        $admin = Pengguna::where('username', 'admin')->firstOrFail();

        $guru = Guru::firstOrFail();

        $kelasSesi = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();
        $kelasLain = Kelas::where('nama_kelas', 'X RPL 2')->firstOrFail();

        $mataPelajaran = MataPelajaran::firstOrFail();

        $siswaDiKelasLain = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelasLain) {
            $query
                ->where('id_kelas', $kelasLain->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelasSesi->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Test',
            'keterangan' => null,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(
            "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswaDiKelasLain->id_siswa}",
            [
                'status' => 'HADIR',
                'waktu_presensi' => '07:15:00',
                'sumber' => 'SISTEM',
                'keterangan' => null,
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswaDiKelasLain->id_siswa,
        ]);
    }

    public function test_membuat_presensi_kelas_mencatat_audit_create(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();
        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Audit Create',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(200);

        $presensi = PresensiKelas::where([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ])->firstOrFail();

        $this->assertDatabaseHas('audit_log', [
            'id_pengguna' => $penggunaGuru->id_pengguna,
            'aksi' => 'CREATE',
            'nama_tabel' => 'presensi_kelas',
            'id_data' => (string) $presensi->id_presensi_kelas,
        ]);
    }

    public function test_mengubah_presensi_kelas_mencatat_audit_koreksi(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();
        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Audit Koreksi',
            'keterangan' => null,
        ]);

        $presensi = PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'IZIN',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Siswa izin.',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_log', [
            'id_pengguna' => $penggunaGuru->id_pengguna,
            'aksi' => 'KOREKSI',
            'nama_tabel' => 'presensi_kelas',
            'id_data' => (string) $presensi->id_presensi_kelas,
        ]);

        $audit = AuditLog::where([
            'id_pengguna' => $penggunaGuru->id_pengguna,
            'aksi' => 'KOREKSI',
            'nama_tabel' => 'presensi_kelas',
            'id_data' => (string) $presensi->id_presensi_kelas,
        ])->latest('id_audit_log')->firstOrFail();

        $this->assertEquals(
            'HADIR',
            $audit->data_sebelum['status']
        );

        $this->assertEquals(
            'IZIN',
            $audit->data_sesudah['status']
        );
    }

    private function buatPenggunaGuru(): Pengguna
    {
        $guru = Guru::firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru-audit-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Audit Test',
            'email' => null,
            'foto' => null,
            'id_guru' => $guru->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where('kode_role', 'GURU')->firstOrFail();

        $pengguna->roles()->attach($roleGuru->id_role);

        return $pengguna;
    }

    public function test_gagal_mencatat_audit_membatalkan_pembuatan_presensi(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Transaction Create',
            'keterangan' => null,
        ]);

        $this->app->instance(
            AuditLogService::class,
            new class extends AuditLogService {
                public function __construct()
                {
                    // Sengaja tidak menjalankan constructor parent.
                }

                public function catat(
                    \App\Models\Pengguna $pengguna,
                    string $aksi,
                    string $namaTabel,
                    string|int $idData,
                    ?array $dataSebelum = null,
                    ?array $dataSesudah = null,
                    ?string $alasan = null
                ): \App\Models\AuditLog {
                    throw new Exception('Simulasi kegagalan audit.');
                }
            }
        );

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(500);

        $this->assertDatabaseMissing('presensi_kelas', [
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
        ]);
    }

    public function test_gagal_mencatat_audit_membatalkan_koreksi_presensi(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Transaction Update',
            'keterangan' => null,
        ]);

        $presensi = PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $this->app->instance(
            AuditLogService::class,
            new class extends AuditLogService {
                public function __construct()
                {
                    // Sengaja tidak menjalankan constructor parent.
                }

                public function catat(
                    \App\Models\Pengguna $pengguna,
                    string $aksi,
                    string $namaTabel,
                    string|int $idData,
                    ?array $dataSebelum = null,
                    ?array $dataSesudah = null,
                    ?string $alasan = null
                ): \App\Models\AuditLog {
                    throw new Exception('Simulasi kegagalan audit.');
                }
            }
        );

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'IZIN',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Siswa izin.',
                ]
            );

        $response->assertStatus(500);

        $this->assertDatabaseHas('presensi_kelas', [
            'id_presensi_kelas' => $presensi->id_presensi_kelas,
            'status' => 'HADIR',
            'keterangan' => null,
        ]);

        $this->assertDatabaseMissing('audit_log', [
            'id_pengguna' => $penggunaGuru->id_pengguna,
            'nama_tabel' => 'presensi_kelas',
            'id_data' => (string) $presensi->id_presensi_kelas,
        ]);
    }

    public function test_audit_create_menyimpan_snapshot_data_yang_benar(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Audit Integrity',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'HADIR',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Presensi masuk.',
                ]
            );

        $response->assertStatus(200);

        $presensi = PresensiKelas::query()
            ->where('id_sesi_presensi', $sesi->id_sesi_presensi)
            ->where('id_siswa', $siswa->id_siswa)
            ->firstOrFail();

        $audit = AuditLog::query()
            ->where('nama_tabel', 'presensi_kelas')
            ->where('id_data', (string) $presensi->id_presensi_kelas)
            ->where('aksi', 'CREATE')
            ->firstOrFail();

        $this->assertEquals(
            $penggunaGuru->id_pengguna,
            $audit->id_pengguna
        );

        $this->assertNull($audit->data_sebelum);

        $this->assertEquals(
            $presensi->id_sesi_presensi,
            $audit->data_sesudah['id_sesi_presensi']
        );

        $this->assertEquals(
            $presensi->id_siswa,
            $audit->data_sesudah['id_siswa']
        );

        $this->assertEquals(
            'HADIR',
            $audit->data_sesudah['status']
        );

        $this->assertEquals(
            '07:05:00',
            $audit->data_sesudah['waktu_presensi']
        );

        $this->assertEquals(
            'GURU',
            $audit->data_sesudah['sumber']
        );

        $this->assertEquals(
            'Presensi masuk.',
            $audit->data_sesudah['keterangan']
        );
    }

    public function test_audit_koreksi_menyimpan_snapshot_sebelum_dan_sesudah(): void
    {
        $penggunaGuru = $this->buatPenggunaGuru();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $penggunaGuru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Audit Koreksi',
            'keterangan' => null,
        ]);

        $presensi = PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'IZIN',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Siswa izin.',
                ]
            );

        $response->assertStatus(200);

        $audit = AuditLog::query()
            ->where('nama_tabel', 'presensi_kelas')
            ->where('id_data', (string) $presensi->id_presensi_kelas)
            ->where('aksi', 'KOREKSI')
            ->latest('id_audit_log')
            ->firstOrFail();

        $this->assertEquals(
            $penggunaGuru->id_pengguna,
            $audit->id_pengguna
        );

        // Snapshot sebelum
        $this->assertEquals(
            'HADIR',
            $audit->data_sebelum['status']
        );

        $this->assertEquals(
            '07:05:00',
            $audit->data_sebelum['waktu_presensi']
        );

        $this->assertEquals(
            'GURU',
            $audit->data_sebelum['sumber']
        );

        $this->assertNull(
            $audit->data_sebelum['keterangan']
        );

        // Snapshot sesudah
        $this->assertEquals(
            'IZIN',
            $audit->data_sesudah['status']
        );

        $this->assertEquals(
            '07:05:00',
            $audit->data_sesudah['waktu_presensi']
        );

        $this->assertEquals(
            'GURU',
            $audit->data_sesudah['sumber']
        );

        $this->assertEquals(
            'Siswa izin.',
            $audit->data_sesudah['keterangan']
        );

        // Alasan audit
        $this->assertEquals(
            'Siswa izin.',
            $audit->alasan
        );
    }

    public function test_audit_mencatat_pengguna_yang_melakukan_koreksi(): void
    {
        $guruPemilik = $this->buatPenggunaGuruBaru('Pemilik');

        $guruPenangan = $this->buatPenggunaGuruBaru('Penangan');

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Actor Audit',
            'keterangan' => null,
        ]);

        $presensi = PresensiKelas::create([
            'id_sesi_presensi' => $sesi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($guruPenangan)
            ->putJson(
                "/api/sesi-presensi/{$sesi->id_sesi_presensi}/presensi/{$siswa->id_siswa}",
                [
                    'status' => 'IZIN',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Koreksi oleh guru penangan.',
                ]
            );

        $response->assertStatus(200);

        $audit = AuditLog::query()
            ->where('nama_tabel', 'presensi_kelas')
            ->where('id_data', (string) $presensi->id_presensi_kelas)
            ->where('aksi', 'KOREKSI')
            ->latest('id_audit_log')
            ->firstOrFail();

        $this->assertEquals(
            $guruPenangan->id_pengguna,
            $audit->id_pengguna
        );

        $this->assertNotEquals(
            $guruPemilik->id_pengguna,
            $audit->id_pengguna
        );
    }

    private function buatPenggunaGuruDenganGuruBerbeda(): Pengguna
    {
        $guru = Guru::query()
            ->whereDoesntHave('pengguna')
            ->firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru-audit-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Audit Test',
            'email' => null,
            'foto' => null,
            'id_guru' => $guru->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $pengguna->roles()->attach(
            $roleGuru->id_role
        );

        return $pengguna;
    }

    private function buatPenggunaGuruBaru(string $suffix): Pengguna
    {
        $guru = Guru::create([
            'nip' => 'TEST' . now()->format('YmdHis') . rand(100, 999),
            'nama_guru' => 'Guru Audit ' . $suffix,
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $pengguna = Pengguna::create([
            'username' => 'guru-audit-' . strtolower($suffix) . '-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Audit ' . $suffix,
            'email' => null,
            'foto' => null,
            'id_guru' => $guru->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $pengguna->roles()->attach(
            $roleGuru->id_role
        );

        return $pengguna;
    }

    /** @return array{0: SesiPresensi, 1: Siswa} */
    private function buatSesiAktifDenganSiswa(): array
    {
        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();
        $guru = Guru::create([
            'nip' => 'AUTH' . strtoupper(substr(uniqid(), -12)),
            'nama_guru' => 'Guru fixture authorization presensi',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);
        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();
        $siswa = Siswa::whereHas('penempatanSiswa', function ($query) use ($kelas) {
            $query
                ->where('id_kelas', $kelas->id_kelas)
                ->where('status', 'AKTIF');
        })->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '09:00:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test authorization koreksi presensi kelas',
            'keterangan' => null,
        ]);

        return [$sesi, $siswa];
    }

    private function buatPenggunaDenganRole(
        string $roleCode,
        ?int $idGuru = null
    ): Pengguna
    {
        $pengguna = Pengguna::create([
            'username' => strtolower($roleCode) . '-auth-' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Test ' . $roleCode,
            'email' => null,
            'id_guru' => $idGuru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $role = Role::where('kode_role', $roleCode)->firstOrFail();
        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }

    private function buatPenugasanWaliKelas(
        Pengguna $waliKelas,
        int $idKelas,
        string $tanggalMulai,
        ?string $tanggalSelesai
    ): Penugasan {
        $roleWaliKelas = Role::where('kode_role', 'WALI_KELAS')->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $waliKelas->id_pengguna,
            'id_role' => $roleWaliKelas->id_role,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture authorization wali kelas.',
        ]);

        PenugasanKelas::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $idKelas,
        ]);

        return $penugasan;
    }
}
