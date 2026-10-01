<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengajaran;
use Illuminate\Database\Seeder;

class PengajaranSeeder extends Seeder
{
    public function run(): void
    {
        $guru = Guru::where(
            'nip',
            '197804302011011006'
        )->firstOrFail();

        $kelas = Kelas::whereHas('jurusan', function ($query) {
            $query->where('kode_jurusan', 'RPL');
        })
            ->where('nama_kelas', 'X RPL 1')
            ->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        Pengajaran::updateOrCreate(
            [
                'id_guru' => $guru->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal_mulai' => '2026-07-01',
            ],
            [
                'jumlah_jp' => 4,
                'tanggal_selesai' => '2026-12-31',
                'status' => 'AKTIF',
                'keterangan' => 'Fixture development dan pengujian ELIES.',
            ]
        );
    }
}
