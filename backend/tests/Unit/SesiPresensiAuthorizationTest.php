<?php

namespace Tests\Unit;

use App\Models\Pengguna;
use App\Services\SesiPresensiAuthorization;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\Guru;
use App\Models\Pengajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SesiPresensi;
use App\Models\Siswa;



class SesiPresensiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_boleh_membuat_sesi_presensi(): void
    {
        $admin = Pengguna::where('username', 'admin')->firstOrFail();

        $authorization = app(SesiPresensiAuthorization::class);

        $boleh = $authorization->canCreate(
            $admin,
            1,
            1,
            Carbon::parse('2026-09-30')
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_membuat_sesi_presensi(): void
    {
        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::updateOrCreate(
            [
                'username' => 'operator-test',
            ],
            [
                'password' => Hash::make('Operator123!'),
                'nama_tampilan' => 'Operator Test',
                'email' => null,
                'status' => 'AKTIF',
            ]
        );

        $operator->roles()->sync([
            $roleOperator->id_role,
        ]);

        $authorization = app(SesiPresensiAuthorization::class);

        $boleh = $authorization->canCreate(
            $operator,
            1,
            1,
            Carbon::parse('2026-09-30')
        );

        $this->assertTrue($boleh);
    }



    private function buatPenggunaGuru(): Pengguna
    {
        $guru = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru_test_' . uniqid(),
            'password' => bcrypt('password'),
            'nama_tampilan' => $guru->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guru->id_guru,
        ]);

        $pengguna->roles()->attach(
            $roleGuru->id_role
        );

        return $pengguna;
    }


    public function test_guru_dengan_pengajaran_sesuai_boleh_membuat_sesi_hari_ini(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 1'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                Carbon::parse('2026-09-30')
            );

            $this->assertTrue($boleh);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_fixture_pengajaran_guru_tersedia(): void
    {
        $guru = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $pengajaran = Pengajaran::query()
            ->where('id_guru', $guru->id_guru)
            ->where('id_kelas', $kelas->id_kelas)
            ->where(
                'id_mata_pelajaran',
                $mataPelajaran->id_mata_pelajaran
            )
            ->where('status', 'AKTIF')
            ->first();

        $this->assertNotNull($pengajaran);

        $this->assertSame(
            $guru->id_guru,
            $pengajaran->id_guru
        );

        $this->assertSame(
            $kelas->id_kelas,
            $pengajaran->id_kelas
        );

        $this->assertSame(
            $mataPelajaran->id_mata_pelajaran,
            $pengajaran->id_mata_pelajaran
        );

        $this->assertSame(
            'AKTIF',
            $pengajaran->status
        );
    }

    public function test_fixture_pengajaran_guru_aktif_pada_tanggal_sesi(): void
    {
        $guru = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $tanggal = Carbon::parse('2026-09-30');

        $pengajaran = Pengajaran::query()
            ->where('id_guru', $guru->id_guru)
            ->where('id_kelas', $kelas->id_kelas)
            ->where(
                'id_mata_pelajaran',
                $mataPelajaran->id_mata_pelajaran
            )
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($query) use ($tanggal) {
                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate(
                        'tanggal_selesai',
                        '>=',
                        $tanggal
                    );
            })
            ->first();

        $this->assertNotNull($pengajaran);

        $this->assertSame(
            '2026-07-01',
            Carbon::parse($pengajaran->tanggal_mulai)->toDateString()
        );

        $this->assertSame(
            '2026-12-31',
            Carbon::parse($pengajaran->tanggal_selesai)->toDateString()
        );

        $this->assertSame(
            'AKTIF',
            $pengajaran->status
        );
    }

    public function test_pengguna_guru_test_memiliki_role_dan_id_guru_yang_benar(): void
    {
        $guru = $this->buatPenggunaGuru();

        $adaRoleGuru = $guru->roles()
            ->where('kode_role', 'GURU')
            ->exists();

        $this->assertTrue($adaRoleGuru);
        $this->assertNotNull($guru->id_guru);

        $guruReferensi = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $this->assertSame(
            $guruReferensi->id_guru,
            $guru->id_guru
        );
    }

    public function test_debug_guru_boleh_membuat_sesi(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $tanggal = Carbon::parse('2026-09-30');

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 1'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                $tanggal
            );

            $this->assertTrue(
                $boleh,
                'canCreate() mengembalikan FALSE.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guru_tanpa_pengajaran_tidak_boleh_membuat_sesi(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 2'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                Carbon::parse('2026-09-30')
            );

            $this->assertFalse($boleh);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guru_dengan_pengajaran_sudah_berakhir_tidak_boleh_membuat_sesi(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 2'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            Pengajaran::create([
                'id_guru' => $guru->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'jumlah_jp' => 4,
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-09-29',
                'status' => 'AKTIF',
                'keterangan' => 'Fixture test pengajaran sudah berakhir.',
            ]);

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                Carbon::parse('2026-09-30')
            );

            $this->assertFalse($boleh);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guru_tidak_boleh_membuat_sesi_untuk_tanggal_besok(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 1'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            $tanggalBesok = Carbon::parse('2026-10-01');

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                $tanggalBesok
            );

            $this->assertFalse($boleh);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guru_tidak_boleh_membuat_sesi_untuk_tanggal_kemarin(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-30 08:00:00')
        );

        try {
            $guru = $this->buatPenggunaGuru();

            $kelas = Kelas::where(
                'nama_kelas',
                'X RPL 1'
            )->firstOrFail();

            $mataPelajaran = MataPelajaran::where(
                'kode_mata_pelajaran',
                'RPL-DASAR'
            )->firstOrFail();

            $tanggalKemarin = Carbon::parse('2026-09-29');

            $authorization = app(
                SesiPresensiAuthorization::class
            );

            $boleh = $authorization->canCreate(
                $guru,
                $kelas->id_kelas,
                $mataPelajaran->id_mata_pelajaran,
                $tanggalKemarin
            );

            $this->assertFalse($boleh);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guru_penangan_boleh_mengelola_presensi_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-001',
            'nama_guru' => 'Guru Penangan Test',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru_penangan_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPenangan->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPenangan->id_guru,
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Penangan',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canManageAttendance(
            $penggunaPenangan,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_lain_tidak_boleh_mengelola_sesi_yang_bukan_miliknya_dan_bukan_penangan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-002',
            'nama_guru' => 'Guru Penangan Test 2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruLain = Guru::create([
            'nip' => 'TEST-GURU-LAIN-001',
            'nama_guru' => 'Guru Lain Test',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru_lain_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruLain->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruLain->id_guru,
        ]);

        $penggunaGuruLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Lain',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canManageAttendance(
            $penggunaGuruLain,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_pemilik_tetap_boleh_mengelola_presensi_setelah_didelegasikan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-003',
            'nama_guru' => 'Guru Penangan Test 3',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru_pemilik_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPemilik->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPemilik->id_guru,
        ]);

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Pemilik',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canManageAttendance(
            $penggunaPemilik,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_admin_boleh_mengelola_presensi_sesi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Admin',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canManageAttendance(
            $admin,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_mengelola_presensi_sesi(): void
    {
        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::updateOrCreate(
            [
                'username' => 'operator-manage-test',
            ],
            [
                'password' => Hash::make('Operator123!'),
                'nama_tampilan' => 'Operator Manage Test',
                'email' => null,
                'status' => 'AKTIF',
            ]
        );

        $operator->roles()->sync([
            $roleOperator->id_role,
        ]);

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Operator',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canManageAttendance(
            $operator,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_penangan_tidak_boleh_mengubah_metadata_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-004',
            'nama_guru' => 'Guru Penangan Test 4',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru_penangan_update_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPenangan->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPenangan->id_guru,
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Update Metadata',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canUpdateSession(
            $penggunaPenangan,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_pemilik_boleh_mengubah_metadata_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru_pemilik_update_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPemilik->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPemilik->id_guru,
        ]);

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Pemilik',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canUpdateSession(
            $penggunaPemilik,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_admin_boleh_mengubah_metadata_sesi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Admin Update',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canUpdateSession(
            $admin,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_mengubah_metadata_sesi(): void
    {
        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::create([
            'username' => 'operator_update_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Update Test',
            'status' => 'AKTIF',
        ]);

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Operator Update',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canUpdateSession(
            $operator,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_lain_tidak_boleh_mengubah_metadata_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruLain = Guru::create([
            'nip' => 'TEST-GURU-LAIN-005',
            'nama_guru' => 'Guru Lain Test 5',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru_lain_update_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruLain->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruLain->id_guru,
        ]);

        $penggunaGuruLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Lain',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canUpdateSession(
            $penggunaGuruLain,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_penangan_boleh_menutup_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-006',
            'nama_guru' => 'Guru Penangan Test 6',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru_penangan_close_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPenangan->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPenangan->id_guru,
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Close Session',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canClose(
            $penggunaPenangan,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_pemilik_boleh_menutup_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru_pemilik_close_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPemilik->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPemilik->id_guru,
        ]);

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Pemilik Close',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canClose(
            $penggunaPemilik,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_admin_boleh_menutup_sesi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Admin Close',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canClose(
            $admin,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_menutup_sesi(): void
    {
        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::create([
            'username' => 'operator_close_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Close Test',
            'status' => 'AKTIF',
        ]);

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Operator Close',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canClose(
            $operator,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_lain_tidak_boleh_menutup_sesi(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruLain = Guru::create([
            'nip' => 'TEST-GURU-LAIN-006',
            'nama_guru' => 'Guru Lain Test 6',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru_lain_close_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruLain->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruLain->id_guru,
        ]);

        $penggunaGuruLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Lain Close',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canClose(
            $penggunaGuruLain,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_admin_boleh_mendelegasikan_guru_penangan(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Delegasi Admin',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canAssignHandler(
            $admin,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_mendelegasikan_guru_penangan(): void
    {
        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator = Pengguna::create([
            'username' => 'operator_delegate_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Delegate Test',
            'status' => 'AKTIF',
        ]);

        $operator->roles()->attach(
            $roleOperator->id_role
        );

        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Delegasi Operator',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canAssignHandler(
            $operator,
            $sesi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_pemilik_tidak_boleh_mendelegasikan_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru_pemilik_delegate_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPemilik->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPemilik->id_guru,
        ]);

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Pemilik Delegasi',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canAssignHandler(
            $penggunaPemilik,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_penangan_tidak_boleh_mendelegasikan_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-007',
            'nama_guru' => 'Guru Penangan Test 7',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru_penangan_delegate_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPenangan->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruPenangan->id_guru,
        ]);

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Penangan Delegasi',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canAssignHandler(
            $penggunaPenangan,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_lain_tidak_boleh_mendelegasikan_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruLain = Guru::create([
            'nip' => 'TEST-GURU-LAIN-007',
            'nama_guru' => 'Guru Lain Test 7',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru_lain_delegate_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruLain->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruLain->id_guru,
        ]);

        $penggunaGuruLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Guru Lain Delegasi',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->canAssignHandler(
            $penggunaGuruLain,
            $sesi
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_aktif_valid_sebagai_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-PENANGAN-008',
            'nama_guru' => 'Guru Penangan Test 8',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Test Validasi Guru Penangan',
            'keterangan' => 'Fixture test authorization.',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->isValidHandler(
            $guruPenangan
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_tidak_aktif_tidak_valid_sebagai_guru_penangan(): void
    {
        $guruTidakAktif = Guru::create([
            'nip' => 'TEST-PENANGAN-009',
            'nama_guru' => 'Guru Tidak Aktif Test 9',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'NONAKTIF',
        ]);

        $authorization = app(
            SesiPresensiAuthorization::class
        );

        $boleh = $authorization->isValidHandler(
            $guruTidakAktif
        );

        $this->assertFalse($boleh);
    }

    public function test_guru_penangan_boleh_mengelola_presensi_kelas(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B121-002',
            'nama_guru' => 'Guru Penangan B12.1',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b121',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B12.1',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
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
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B12.1',
            'keterangan' => null,
        ]);

        $authorization = new SesiPresensiAuthorization();

        $boleh = $authorization->canManageAttendance(
            $penggunaPenangan,
            $sesiPresensi
        );

        $this->assertTrue($boleh);
    }

    public function test_guru_lain_tidak_boleh_mengelola_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B122-002',
            'nama_guru' => 'Guru Penangan B12.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruLain = Guru::create([
            'nip' => 'TEST-B122-003',
            'nama_guru' => 'Guru Lain B12.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru-b122',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Lain B12.2',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruLain->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaGuruLain->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
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
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B12.2',
            'keterangan' => null,
        ]);

        $authorization = new SesiPresensiAuthorization();

        $boleh = $authorization->canManageAttendance(
            $penggunaGuruLain,
            $sesiPresensi
        );

        $this->assertFalse($boleh);
    }

    public function test_admin_boleh_mengelola_presensi(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b123',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B12.3',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
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
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B12.3',
            'keterangan' => null,
        ]);

        $authorization = new SesiPresensiAuthorization();

        $boleh = $authorization->canManageAttendance(
            $admin,
            $sesiPresensi
        );

        $this->assertTrue($boleh);
    }

    public function test_operator_boleh_mengelola_presensi(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator-b124',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator B12.4',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
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
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B12.4',
            'keterangan' => null,
        ]);

        $authorization = new SesiPresensiAuthorization();

        $boleh = $authorization->canManageAttendance(
            $operator,
            $sesiPresensi
        );

        $this->assertTrue($boleh);
    }

    public function test_siswa_tidak_boleh_mengelola_presensi(): void
    {
        $siswa = Siswa::firstOrFail();

        $penggunaSiswa = Pengguna::where(
            'id_siswa',
            $siswa->id_siswa
        )->firstOrFail();

        $penggunaSiswa->load('roles');

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
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
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi B12.5',
            'keterangan' => null,
        ]);

        $authorization = new SesiPresensiAuthorization();

        $boleh = $authorization->canManageAttendance(
            $penggunaSiswa,
            $sesiPresensi
        );

        $this->assertFalse($boleh);
    }
}
