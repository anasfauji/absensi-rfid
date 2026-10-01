<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kalender;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\PenempatanSiswa;
use App\Services\AttendanceEvaluationService;
use App\Models\Kegiatan;
use App\Models\KegiatanSiswa;
use App\Models\KegiatanKelas;
use App\Models\KegiatanJurusan;
use App\Models\PresensiGate;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\PresensiKelas;
use App\Models\SesiPresensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;


class AttendanceEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hari_tidak_aktif_tidak_mewajibkan_siswa_hadir(): void
    {
        $tanggal = '2026-09-23';

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST',
            'npsn' => '99999999',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL TEST',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST001',
            'nisn' => 'TESTNISN001',
            'nama_siswa' => 'Siswa Test',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'TIDAK_AKTIF',
            'keterangan' => 'Hari libur untuk testing',
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            \Carbon\Carbon::parse($tanggal)
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_hari_aktif_tanpa_kegiatan_mewajibkan_siswa_hadir(): void
    {
        $tanggal = '2026-09-23';
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST2',
            'npsn' => '99999998',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL TEST 2',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST002',
            'nisn' => 'TESTNISN002',
            'nama_siswa' => 'Siswa Test 2',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
            'keterangan' => 'Hari aktif untuk testing',
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            \Carbon\Carbon::parse($tanggal)
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_kegiatan_scope_siswa_membuat_siswa_tidak_wajib_hadir(): void
    {
        $tanggal = '2026-09-23';

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST3',
            'npsn' => '99999997',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL TEST 3',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST003',
            'nisn' => 'TESTNISN003',
            'nama_siswa' => 'Siswa Test 3',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
            'keterangan' => 'Hari aktif untuk testing',
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL Siswa Test 3',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => 'Testing scope siswa',
        ]);

        KegiatanSiswa::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_siswa' => $siswa->id_siswa,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            \Carbon\Carbon::parse($tanggal)
        );
        Carbon::setTestNow();

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_kegiatan_scope_kelas_membuat_siswa_tidak_wajib_hadir(): void
    {
        $tanggal = '2026-09-23';

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST4',
            'npsn' => '99999996',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL TEST 4',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST004',
            'nisn' => 'TESTNISN004',
            'nama_siswa' => 'Siswa Test 4',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
            'keterangan' => 'Hari aktif untuk testing',
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL Kelas Test 4',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => 'Testing scope kelas',
        ]);

        KegiatanKelas::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_kelas' => $kelas->id_kelas,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            \Carbon\Carbon::parse($tanggal)
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_kegiatan_scope_jurusan_membuat_siswa_tidak_wajib_hadir(): void
    {
        $tanggal = '2026-09-23';

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST5',
            'npsn' => '99999995',
            'status' => 'AKTIF',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $jurusan = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL TEST 5',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST005',
            'nisn' => 'TESTNISN005',
            'nama_siswa' => 'Siswa Test 5',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
            'keterangan' => 'Hari aktif untuk testing',
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL Jurusan RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => 'Testing scope jurusan',
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $jurusan->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            \Carbon\Carbon::parse($tanggal)
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_kegiatan_scope_jurusan_lain_tidak_mempengaruhi_siswa(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $tkj = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'TKJ',
            'nama_jurusan' => 'Teknik Komputer dan Jaringan',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260001',
            'nisn' => '0060000001',
            'nama_siswa' => 'Siswa RPL',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL TKJ',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $tkj->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_kegiatan_status_rencana_tidak_membuat_siswa_tidak_wajib_hadir(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260007',
            'nisn' => '0060000007',
            'nama_siswa' => 'Siswa RPL Rencana',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'RENCANA',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_kegiatan_status_selesai_tidak_membuat_siswa_tidak_wajib_hadir(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260008',
            'nisn' => '0060000008',
            'nama_siswa' => 'Siswa RPL Selesai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'SELESAI',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_kegiatan_status_dibatalkan_tidak_membuat_siswa_tidak_wajib_hadir(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260009',
            'nisn' => '0060000009',
            'nama_siswa' => 'Siswa RPL Dibatalkan',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'DIBATALKAN',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_kegiatan_berlaku_pada_tanggal_mulai(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260010',
            'nisn' => '0060000010',
            'nama_siswa' => 'Siswa RPL Batas Mulai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-20',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-20')
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_kegiatan_berlaku_pada_tanggal_selesai(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260010',
            'nisn' => '0060000010',
            'nama_siswa' => 'Siswa RPL Batas Mulai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-30',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-30')
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_sebelum_tanggal_mulai_kegiatan_siswa_tetap_wajib_hadir(): void
    {

        Carbon::setTestNow(
            Carbon::parse('2026-09-19 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260010',
            'nisn' => '0060000010',
            'nama_siswa' => 'Siswa RPL Batas Mulai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        // Kalender:
        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-19',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        // Kegiatan:
        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-19')
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_setelah_tanggal_selesai_kegiatan_siswa_tetap_wajib_hadir(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260010',
            'nisn' => '0060000010',
            'nama_siswa' => 'Siswa RPL Batas Mulai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        // Kalender:
        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-10-01',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        // Kegiatan:
        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL RPL',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-30',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanJurusan::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_jurusan' => $rpl->id_jurusan,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-10-01')
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_penempatan_siswa_menggunakan_kelas_yang_berlaku_pada_tanggal_evaluasi(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $tkj = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'TKJ',
            'nama_jurusan' => 'Teknik Komputer dan Jaringan',
            'status' => 'AKTIF',
        ]);

        $kelasRpl = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $kelasTkj = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $tkj->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X TKJ 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260014',
            'nisn' => '0060000014',
            'nama_siswa' => 'Siswa Pindah Kelas',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        /*
     * Penempatan lama:
     * X RPL 1
     * Berlaku 01 Juli sampai 19 September.
     */
        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasRpl->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-19',
            'status' => 'AKTIF',
        ]);

        /*
     * Penempatan baru:
     * X TKJ 1
     * Mulai 20 September.
     */
        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTkj->id_kelas,
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        /*
     * Hari sekolah aktif.
     */
        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-14',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        /*
     * PKL hanya untuk X RPL 1.
     */
        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL X RPL 1',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-10',
            'tanggal_selesai' => '2026-09-15',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanKelas::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_kelas' => $kelasRpl->id_kelas,
        ]);

        $service = app(AttendanceEvaluationService::class);

        /*
     * Evaluasi dilakukan pada 14 September.
     *
     * Pada tanggal ini siswa masih berada di X RPL 1.
     */
        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-14')
        );

        $this->assertFalse($hasil['wajib_hadir']);
        $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    }

    public function test_penempatan_siswa_menggunakan_kelas_yang_berlaku_setalah_tanggal_pindah_kelas(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-20 12:00:00')
        );

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $tkj = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'TKJ',
            'nama_jurusan' => 'Teknik Komputer dan Jaringan',
            'status' => 'AKTIF',
        ]);

        $kelasRpl = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $kelasTkj = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $tkj->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X TKJ 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260014',
            'nisn' => '0060000014',
            'nama_siswa' => 'Siswa Pindah Kelas',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        /*
     * Penempatan lama:
     * X RPL 1
     * Berlaku 01 Juli sampai 19 September.
     */
        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasRpl->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-19',
            'status' => 'AKTIF',
        ]);

        /*
     * Penempatan baru:
     * X TKJ 1
     * Mulai 20 September.
     */
        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTkj->id_kelas,
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        /*
     * Hari sekolah aktif.
     */
        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-20',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        /*
     * PKL hanya untuk X RPL 1.
     */
        $kegiatan = Kegiatan::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_kegiatan' => 'PKL X RPL 1',
            'jenis_kegiatan' => 'PKL',
            'tanggal_mulai' => '2026-09-10',
            'tanggal_selesai' => '2026-09-15',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        KegiatanKelas::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_kelas' => $kelasRpl->id_kelas,
        ]);

        $service = app(AttendanceEvaluationService::class);

        /*
     * Evaluasi dilakukan pada 14 September.
     *
     * Pada tanggal ini siswa masih berada di X RPL 1.
     */
        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-20')
        );

        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_siswa_tanpa_penempatan_efektif_menghasilkan_data_tidak_lengkap(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $siswa = Siswa::create([
            'nis' => '260016',
            'nisn' => '0060000016',
            'nama_siswa' => 'Siswa Tanpa Penempatan',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertNull($hasil['wajib_hadir']);
        $this->assertSame('DATA_TIDAK_LENGKAP', $hasil['status']);
    }

    public function test_siswa_dengan_penempatan_tidak_aktif_menghasilkan_data_tidak_lengkap(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260017',
            'nisn' => '0060000017',
            'nama_siswa' => 'Siswa Penempatan Tidak Aktif',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        /*
     * Siswa masih aktif,
     * tetapi penempatannya tidak aktif.
     */
        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'SELESAI',
        ]);

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => '2026-09-23',
            'status_hari' => 'AKTIF',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertNull($hasil['wajib_hadir']);
        $this->assertSame('DATA_TIDAK_LENGKAP', $hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_gate_menghasilkan_hadir(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260017',
            'nisn' => '0060000017',
            'nama_siswa' => 'Siswa Penempatan Tidak Aktif',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        PresensiGate::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => $tanggal,
            'waktu_masuk' => $tanggal->copy()->setTime(7, 5),
            'waktu_keluar' => null,
            'status' => 'HADIR',
            'sumber_masuk' => 'RFID',
            'sumber_keluar' => null,
            'keterangan' => null,
            'dibuat_oleh' => null,
            'diubah_oleh' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('HADIR', $hasil['status']);
    }

    public function test_presensi_gate_pada_tanggal_lain_tidak_membuat_siswa_hadir(): void
    {

        Carbon::setTestNow(
            Carbon::parse('2026-09-23 12:00:00')
        );
        $tanggalEvaluasi = Carbon::parse('2026-09-23');
        $tanggalGate = Carbon::parse('2026-09-22');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggalEvaluasi,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260018',
            'nisn' => '0060000018',
            'nama_siswa' => 'Siswa RFID Tanggal Lain',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        PresensiGate::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => $tanggalGate,
            'waktu_masuk' => $tanggalGate->copy()->setTime(7, 5),
            'waktu_keluar' => null,
            'status' => 'HADIR',
            'sumber_masuk' => 'RFID',
            'sumber_keluar' => null,
            'keterangan' => null,
            'dibuat_oleh' => null,
            'diubah_oleh' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggalEvaluasi
        );
        Carbon::setTestNow();

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_kelas_hadir_menghasilkan_hadir(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260020',
            'nisn' => '0060000020',
            'nama_siswa' => 'Siswa Presensi Kelas Hadir',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $guru = Guru::create([
            'nip' => '197804302011011006',
            'nama_guru' => 'Kukuh Suprapto',
            'jenis_kelamin' => 'L',
            'email' => 'kukuh@example.com',
            'nomor_telepon' => '081234567890',
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $mataPelajaran = MataPelajaran::create([
            'kode_mata_pelajaran' => 'RPL001',
            'nama_mata_pelajaran' => 'Pemrograman Web',
            'kelompok' => 'KEJURUAN',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Pengenalan HTML',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => $tanggal->copy()->setTime(8, 5),
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('HADIR', $hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_kelas_terlambat_menghasilkan_hadir(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260021',
            'nisn' => '0060000021',
            'nama_siswa' => 'Siswa Presensi Kelas Terlambat',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $guru = Guru::create([
            'nip' => '197804302011011006',
            'nama_guru' => 'Kukuh Suprapto',
            'jenis_kelamin' => 'L',
            'email' => 'kukuh@example.com',
            'nomor_telepon' => '081234567890',
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $mataPelajaran = MataPelajaran::create([
            'kode_mata_pelajaran' => 'RPL001',
            'nama_mata_pelajaran' => 'Pemrograman Web',
            'kelompok' => 'KEJURUAN',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Pengenalan HTML',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'TERLAMBAT',
            'waktu_presensi' => $tanggal->copy()->setTime(8, 15),
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('HADIR', $hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_kelas_sakit_menghasilkan_sakit(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260022',
            'nisn' => '0060000022',
            'nama_siswa' => 'Siswa Presensi Kelas Sakit',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $guru = Guru::create([
            'nip' => '197804302011011006',
            'nama_guru' => 'Kukuh Suprapto',
            'jenis_kelamin' => 'L',
            'email' => 'kukuh@example.com',
            'nomor_telepon' => '081234567890',
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $mataPelajaran = MataPelajaran::create([
            'kode_mata_pelajaran' => 'RPL001',
            'nama_mata_pelajaran' => 'Pemrograman Web',
            'kelompok' => 'KEJURUAN',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Pengenalan HTML',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'SAKIT',
            'waktu_presensi' => $tanggal->copy()->setTime(8, 5),
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('SAKIT', $hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_kelas_izin_menghasilkan_izin(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260023',
            'nisn' => '0060000023',
            'nama_siswa' => 'Siswa Presensi Kelas Izin',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $guru = Guru::create([
            'nip' => '197804302011011006',
            'nama_guru' => 'Kukuh Suprapto',
            'jenis_kelamin' => 'L',
            'email' => 'kukuh@example.com',
            'nomor_telepon' => '081234567890',
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $mataPelajaran = MataPelajaran::create([
            'kode_mata_pelajaran' => 'RPL001',
            'nama_mata_pelajaran' => 'Pemrograman Web',
            'kelompok' => 'KEJURUAN',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Pengenalan HTML',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'IZIN',
            'waktu_presensi' => $tanggal->copy()->setTime(8, 5),
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('IZIN', $hasil['status']);
    }

    public function test_siswa_wajib_hadir_dan_memiliki_presensi_kelas_dispensasi_menghasilkan_dispensasi(): void
    {
        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260024',
            'nisn' => '0060000024',
            'nama_siswa' => 'Siswa Presensi Kelas Dispensasi',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $guru = Guru::create([
            'nip' => '197804302011011006',
            'nama_guru' => 'Kukuh Suprapto',
            'jenis_kelamin' => 'L',
            'email' => 'kukuh@example.com',
            'nomor_telepon' => '081234567890',
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $mataPelajaran = MataPelajaran::create([
            'kode_mata_pelajaran' => 'RPL001',
            'nama_mata_pelajaran' => 'Pemrograman Web',
            'kelompok' => 'KEJURUAN',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => $tanggal,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Pengenalan HTML',
            'keterangan' => null,
        ]);

        PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'DISPENSASI',
            'waktu_presensi' => $tanggal->copy()->setTime(8, 5),
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('DISPENSASI', $hasil['status']);
    }

    public function test_siswa_tanpa_fakta_presensi_sebelum_cutoff_menghasilkan_null(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 23:59:58')
        );

        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260026',
            'nisn' => '0060000026',
            'nama_siswa' => 'Siswa Tanpa Fakta Presensi',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertNull($hasil['status']);

        Carbon::setTestNow();
    }

    public function test_siswa_tanpa_fakta_presensi_tepat_pada_cutoff_menghasilkan_alpa(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-23 23:59:59')
        );

        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260025',
            'nisn' => '0060000025',
            'nama_siswa' => 'Siswa Tanpa Fakta Presensi',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('ALPA', $hasil['status']);

        Carbon::setTestNow();
    }

    public function test_siswa_tanpa_fakta_presensi_setelah_cutoff(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-24 00:00:00')
        );

        $tanggal = Carbon::parse('2026-09-23');

        $sekolah = Sekolah::create([
            'kode_sekolah' => 'SMKN8JEMBER',
            'nama_sekolah' => 'SMK Negeri 8 Jember',
            'npsn' => '20554690',
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

        Kalender::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tanggal' => $tanggal,
            'status_hari' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => '260025',
            'nisn' => '0060000025',
            'nama_siswa' => 'Siswa Tanpa Fakta Presensi',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $rpl = Jurusan::create([
            'id_sekolah' => $sekolah->id_sekolah,
            'kode_jurusan' => 'RPL',
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_jurusan' => $rpl->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RPL 1',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AttendanceEvaluationService::class);

        $hasil = $service->evaluate(
            $siswa->id_siswa,
            $tanggal
        );

        $this->assertTrue($hasil['wajib_hadir']);
        $this->assertSame('ALPA', $hasil['status']);

        Carbon::setTestNow();
    }
}
