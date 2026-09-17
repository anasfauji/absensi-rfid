<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where(
            'nama_tahun_ajaran',
            '2026/2027'
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $jurusan = Jurusan::where(
            'kode_jurusan',
            'RPL'
        )
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $kelas = [
            [
                'tingkat' => 10,
                'nama_kelas' => 'X RPL 1',
            ],
            [
                'tingkat' => 10,
                'nama_kelas' => 'X RPL 2',
            ],
            [
                'tingkat' => 11,
                'nama_kelas' => 'XI RPL 1',
            ],
            [
                'tingkat' => 11,
                'nama_kelas' => 'XI RPL 2',
            ],
            [
                'tingkat' => 12,
                'nama_kelas' => 'XII RPL 1',
            ],
            [
                'tingkat' => 12,
                'nama_kelas' => 'XII RPL 2',
            ],
        ];

        foreach ($kelas as $item) {
            Kelas::updateOrCreate(
                [
                    'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                    'id_jurusan' => $jurusan->id_jurusan,
                    'nama_kelas' => $item['nama_kelas'],
                ],
                [
                    'tingkat' => $item['tingkat'],
                    'status' => 'AKTIF',
                ]
            );
        }
    }
}
