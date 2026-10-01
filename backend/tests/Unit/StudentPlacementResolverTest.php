<?php

namespace Tests\Unit;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PenempatanSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\StudentPlacementResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPlacementResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengembalikan_penempatan_yang_berlaku_pada_tanggal_tertentu(): void
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
            'nama_siswa' => 'Siswa Test Resolver',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        $penempatan = PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $resolver = app(StudentPlacementResolver::class);

        $hasil = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse($tanggal)
        );

        $this->assertNotNull($hasil);

        $this->assertSame(
            $penempatan->id_penempatan,
            $hasil->id_penempatan
        );

        $this->assertSame(
            $kelas->id_kelas,
            $hasil->id_kelas
        );

        $this->assertNotNull($hasil->kelas);
        $this->assertNotNull($hasil->kelas->jurusan);

        $this->assertSame(
            'X RPL TEST',
            $hasil->kelas->nama_kelas
        );

        $this->assertSame(
            'RPL',
            $hasil->kelas->jurusan->kode_jurusan
        );
    }

    public function test_tidak_mengembalikan_penempatan_sebelum_tanggal_mulai(): void
    {
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
            'nis' => 'TEST002',
            'nisn' => 'TESTNISN002',
            'nama_siswa' => 'Siswa Sebelum Mulai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $resolver = app(StudentPlacementResolver::class);

        $hasil = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse('2026-06-30')
        );

        $this->assertNull($hasil);
    }

    public function test_tidak_mengembalikan_penempatan_setelah_tanggal_selesai(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST',
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
            'nama_kelas' => 'X RPL TEST',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST003',
            'nisn' => 'TESTNISN003',
            'nama_siswa' => 'Siswa Setelah Selesai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-19',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $resolver = app(StudentPlacementResolver::class);

        $hasil = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse('2026-09-20')
        );

        $this->assertNull($hasil);
    }

    public function test_menggunakan_penempatan_kelas_sesuai_tanggal(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST',
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
            'nis' => 'TEST004',
            'nisn' => 'TESTNISN004',
            'nama_siswa' => 'Siswa Pindah Kelas',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasRpl->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-09-19',
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTkj->id_kelas,
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => null,
            'status' => 'AKTIF',
            'keterangan' => null,
        ]);

        $resolver = app(StudentPlacementResolver::class);

        $penempatanSebelumPindah = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse('2026-09-19')
        );

        $penempatanSetelahPindah = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse('2026-09-20')
        );

        $this->assertNotNull($penempatanSebelumPindah);
        $this->assertNotNull($penempatanSetelahPindah);

        $this->assertSame(
            $kelasRpl->id_kelas,
            $penempatanSebelumPindah->id_kelas
        );

        $this->assertSame(
            $kelasTkj->id_kelas,
            $penempatanSetelahPindah->id_kelas
        );

        $this->assertSame(
            'RPL',
            $penempatanSebelumPindah->kelas->jurusan->kode_jurusan
        );

        $this->assertSame(
            'TKJ',
            $penempatanSetelahPindah->kelas->jurusan->kode_jurusan
        );
    }

    public function test_tidak_mengembalikan_penempatan_yang_tidak_aktif(): void
    {
        $sekolah = Sekolah::create([
            'kode_sekolah' => 'TEST',
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
            'nama_kelas' => 'X RPL TEST',
            'status' => 'AKTIF',
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST005',
            'nisn' => 'TESTNISN005',
            'nama_siswa' => 'Siswa Penempatan Selesai',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'SELESAI',
            'keterangan' => null,
        ]);

        $resolver = app(StudentPlacementResolver::class);

        $hasil = $resolver->getEffectivePlacement(
            $siswa->id_siswa,
            Carbon::parse('2026-09-23')
        );

        $this->assertNull($hasil);
    }
}
