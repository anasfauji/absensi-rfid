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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
            'status_sesi' => 'DRAFT',
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
}
