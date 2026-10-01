<?php

namespace Tests\Unit;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\Jurusan;
use App\Models\PenempatanSiswa;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Penugasan;
use App\Models\PenugasanKelas;
use App\Models\PenugasanJurusan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AttendanceScope;
use App\Services\StudentPlacementResolver;
use App\Models\SesiPresensi;
use App\Models\Guru;
use App\Models\MataPelajaran;

class AttendanceScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_dapat_melihat_evaluasi_presensi_siswa_mana_pun(): void
    {
        $admin = $this->buatPenggunaDenganRole('ADMIN');

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $admin,
            1,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_operator_dapat_melihat_evaluasi_presensi_siswa_mana_pun(): void
    {
        $operator = $this->buatPenggunaDenganRole('OPERATOR');

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $operator,
            1,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_ks_dapat_melihat_evaluasi_presensi_siswa_mana_pun(): void
    {
        $ks = $this->buatPenggunaDenganRole('KS');

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $ks,
            1,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_waka_dapat_melihat_evaluasi_presensi_siswa_mana_pun(): void
    {
        $waka = $this->buatPenggunaDenganRole('WAKA');

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $waka,
            1,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_siswa_tidak_dapat_melihat_evaluasi_siswa_lain_secara_school_wide(): void
    {
        $siswa = $this->buatPenggunaDenganRole('SISWA');

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $siswa,
            1,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_kaprog_dapat_melihat_evaluasi_siswa_dari_jurusannya(): void
    {
        $kaprog = $this->buatPenggunaDenganRole('KAPROG');

        $jurusanRpl = Jurusan::where('kode_jurusan', 'RPL')->firstOrFail();

        $this->buatPenugasanKaproG(
            $kaprog,
            $jurusanRpl->id_jurusan
        );


        $scope = $this->buatAttendanceScope();

        $siswaRpl = \App\Models\Siswa::where('nis', '260001')->firstOrFail();
        $hasil = $scope->canViewStudentEvaluation(
            $kaprog,
            $siswaRpl->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    private function buatAttendanceScope(): AttendanceScope
    {
        return new AttendanceScope(
            app(StudentPlacementResolver::class)
        );
    }

    public function test_kaprog_tidak_dapat_melihat_siswa_dari_jurusan_lain(): void
    {
        $kaprog = $this->buatPenggunaDenganRole('KAPROG');

        $jurusanRpl = Jurusan::where(
            'kode_jurusan',
            'RPL'
        )->firstOrFail();

        $this->buatPenugasanKaproG(
            $kaprog,
            $jurusanRpl->id_jurusan
        );

        $siswaTkr = $this->buatSiswaDariJurusanLain();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $kaprog,
            $siswaTkr->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_wali_kelas_dapat_melihat_siswa_di_kelasnya(): void
    {
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');

        $siswa = \App\Models\Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatan = \App\Models\PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $penempatan->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $waliKelas,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_wali_kelas_tidak_dapat_melihat_siswa_di_kelas_lain(): void
    {
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');

        $siswaRpl1 = \App\Models\Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatanRpl1 = \App\Models\PenempatanSiswa::where(
            'id_siswa',
            $siswaRpl1->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $this->buatPenugasanWaliKelas(
            $waliKelas,
            $penempatanRpl1->id_kelas
        );

        $siswaRpl2 = $this->buatSiswaDariKelasLain();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $waliKelas,
            $siswaRpl2->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_siswa_dapat_melihat_evaluasi_presensinya_sendiri(): void
    {
        $siswa = \App\Models\Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penggunaSiswa = \App\Models\Pengguna::where(
            'id_siswa',
            $siswa->id_siswa
        )->firstOrFail();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $penggunaSiswa,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_siswa_tidak_dapat_melihat_evaluasi_siswa_lain(): void
    {
        $siswaA = \App\Models\Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $siswaB = \App\Models\Siswa::where(
            'nis',
            '260002'
        )->firstOrFail();

        $penggunaSiswaA = \App\Models\Pengguna::where(
            'id_siswa',
            $siswaA->id_siswa
        )->firstOrFail();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $penggunaSiswaA,
            $siswaB->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_guru_dapat_melihat_siswa_dari_sesi_presensinya(): void
    {
        $guru = $this->buatPenggunaGuru();

        $siswa = \App\Models\Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $penempatan = \App\Models\PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $this->buatSesiPresensiGuru(
            $guru,
            $penempatan->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $guru,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_guru_tidak_dapat_melihat_siswa_dari_kelas_lain(): void
    {
        $guru = $this->buatPenggunaGuru();

        $siswaRpl1 = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $siswaRpl2 = $this->buatSiswaDariKelasLain();

        $penempatanRpl1 = PenempatanSiswa::where(
            'id_siswa',
            $siswaRpl1->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        // Guru memiliki sesi di kelas siswa RPL 1.
        $this->buatSesiPresensiGuru(
            $guru,
            $penempatanRpl1->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $guru,
            $siswaRpl2->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }


    public function test_guru_tidak_dapat_melihat_siswa_dari_sesi_guru_lain(): void
    {
        $guruA = $this->buatPenggunaGuru();
        $guruB = $this->buatPenggunaGuruKedua();

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

        // Guru A memiliki sesi presensi di kelas siswa.
        $this->buatSesiPresensiGuru(
            $guruA,
            $penempatan->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        // Guru B tidak memiliki sesi tersebut.
        $hasil = $scope->canViewStudentEvaluation(
            $guruB,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }


    public function test_guru_tidak_dapat_melihat_siswa_dari_sesi_pada_tanggal_lain(): void
    {
        $guru = $this->buatPenggunaGuru();

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

        // Guru memiliki sesi pada 23 September.
        $this->buatSesiPresensiGuru(
            $guru,
            $penempatan->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        // Evaluasi diminta untuk 24 September.
        $hasil = $scope->canViewStudentEvaluation(
            $guru,
            $siswa->id_siswa,
            Carbon::parse('2026-09-24')
        );

        $this->assertFalse($hasil);
    }

    public function test_pengguna_dengan_role_kaproG_dan_guru_menggunakan_or_scope(): void
    {
        $guru = $this->buatPenggunaGuru();

        $roleKaproG = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $guru->roles()->attach($roleKaproG->id_role);

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

        $this->buatSesiPresensiGuru(
            $guru,
            $penempatan->id_kelas
        );

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $guru,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertTrue($hasil);
    }

    public function test_kaprog_tidak_dapat_melihat_siswa_di_luar_periode_penugasan(): void
    {
        $kaprog = $this->buatPenggunaDenganRole('KAPROG');

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

        $jurusan = $penempatan
            ->kelas
            ->jurusan;

        $roleKaproG = Role::where(
            'kode_role',
            'KAPROG'
        )->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $kaprog->id_pengguna,
            'id_role' => $roleKaproG->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-20',
            'status' => 'AKTIF',
            'keterangan' => 'Test periode KAPROG',
        ]);

        PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $jurusan->id_jurusan,
        ]);

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $kaprog,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }



    public function test_wali_kelas_tidak_dapat_melihat_siswa_di_luar_periode_penugasan(): void
    {
        $waliKelas = $this->buatPenggunaDenganRole('WALI_KELAS');

        $siswa = Siswa::where('nis', '260001')
            ->firstOrFail();

        $penempatan = PenempatanSiswa::where(
            'id_siswa',
            $siswa->id_siswa
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $role = Role::where('kode_role', 'WALI_KELAS')
            ->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $waliKelas->id_pengguna,
            'id_role' => $role->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-20',
            'status' => 'AKTIF',
            'keterangan' => 'Test periode WALI KELAS',
        ]);

        PenugasanKelas::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $penempatan->id_kelas,
        ]);

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $waliKelas,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }


    public function test_pengguna_tanpa_role_tidak_dapat_melihat_evaluasi(): void
    {
        $pengguna = Pengguna::create([
            'username' => 'tanpa_role_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Pengguna Tanpa Role',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $pengguna,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_kaprog_tidak_dapat_melihat_siswa_tanpa_penempatan_efektif(): void
    {
        $kaprog = $this->buatPenggunaDenganRole('KAPROG');

        $siswa = Siswa::create([
            'nis' => 'TEST-TANPA-PENEMPATAN',
            'nama_siswa' => 'Siswa Tanpa Penempatan',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $kaprog,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    public function test_guru_tanpa_id_guru_tidak_dapat_melihat_evaluasi(): void
    {
        $guru = $this->buatPenggunaDenganRole('GURU');

        $siswa = Siswa::where(
            'nis',
            '260001'
        )->firstOrFail();

        $scope = $this->buatAttendanceScope();

        $hasil = $scope->canViewStudentEvaluation(
            $guru,
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertFalse($hasil);
    }

    //=============Helper============

    private function buatPenggunaDenganRole(string $kodeRole): Pengguna
    {
        $pengguna = Pengguna::create([
            'username' => strtolower($kodeRole) . '_test',
            'password' => bcrypt('password'),
            'nama_tampilan' => $kodeRole . ' Test',
            'status' => 'AKTIF',
        ]);

        $role = Role::where('kode_role', $kodeRole)->firstOrFail();

        $pengguna->roles()->attach($role->id_role);

        return $pengguna;
    }

    private function buatPenugasanKaproG(
        Pengguna $pengguna,
        int $idJurusan
    ): void {
        $roleKaproG = Role::where('kode_role', 'KAPROG')->firstOrFail();

        $penugasan = \App\Models\Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $roleKaproG->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Test KAPROG',
        ]);

        \App\Models\PenugasanJurusan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_jurusan' => $idJurusan,
        ]);
    }

    private function buatSiswaDariJurusanLain(): Siswa
    {
        $tahunAjaran = TahunAjaran::where(
            'nama_tahun_ajaran',
            '2026/2027'
        )->firstOrFail();

        $jurusanLain = Jurusan::where(
            'kode_jurusan',
            'TKR'
        )->firstOrFail();

        $kelasLain = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusanLain->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X TKR TEST',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST-TKR-001',
            'nama_siswa' => 'Siswa Test TKR',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasLain->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test scope KAPROG',
        ]);

        return $siswa;
    }

    private function buatPenugasanWaliKelas(
        Pengguna $pengguna,
        int $idKelas
    ): void {
        $roleWaliKelas = Role::where(
            'kode_role',
            'WALI_KELAS'
        )->firstOrFail();

        $penugasan = Penugasan::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_role' => $roleWaliKelas->id_role,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Test WALI KELAS',
        ]);

        PenugasanKelas::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'id_kelas' => $idKelas,
        ]);
    }

    private function buatSiswaDariKelasLain(): Siswa
    {
        $kelasLain = Kelas::where(
            'nama_kelas',
            'X RPL 2'
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $siswa = Siswa::create([
            'nis' => 'TEST-RPL2-001',
            'nama_siswa' => 'Siswa Test RPL 2',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasLain->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => 'Fixture test scope WALI KELAS',
        ]);

        return $siswa;
    }

    private function buatSesiPresensiGuru(
        Pengguna $pengguna,
        int $idKelas
    ): SesiPresensi {
        $this->assertNotNull(
            $pengguna->id_guru,
            'Pengguna GURU harus memiliki id_guru.'
        );

        $mataPelajaran = \App\Models\MataPelajaran::firstOrFail();

        return SesiPresensi::create([
            'id_guru' => $pengguna->id_guru,
            'id_kelas' => $idKelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-23',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Test Scope GURU',
            'keterangan' => 'Fixture test scope GURU',
        ]);
    }

    private function buatPenggunaGuru(): Pengguna
    {
        $guru = Guru::firstOrFail();

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

    private function buatPenggunaGuruKedua(): Pengguna
    {
        $guruKedua = Guru::create([
            'nip' => 'TEST-GURU-002',
            'nama_guru' => 'Guru Test Kedua',
            'jenis_kelamin' => 'L',
            'email' => 'guru2@test.local',
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $pengguna = Pengguna::create([
            'username' => 'guru_test_2',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruKedua->nama_guru,
            'status' => 'AKTIF',
            'id_guru' => $guruKedua->id_guru,
        ]);

        $pengguna->roles()->attach(
            $roleGuru->id_role
        );

        return $pengguna;
    }
}
