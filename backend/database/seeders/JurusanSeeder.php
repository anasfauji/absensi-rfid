<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::where(
            'kode_sekolah',
            'SMKN8JEMBER'
        )->firstOrFail();

        $jurusan = [
            [
                'kode_jurusan' => 'TKR',
                'nama_jurusan' => 'Teknik Kendaraan Ringan',
            ],
            [
                'kode_jurusan' => 'TSM',
                'nama_jurusan' => 'Teknik Sepeda Motor',
            ],
            [
                'kode_jurusan' => 'TKJ',
                'nama_jurusan' => 'Teknik Komputer dan Jaringan',
            ],
            [
                'kode_jurusan' => 'RPL',
                'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            ],
            [
                'kode_jurusan' => 'DKV',
                'nama_jurusan' => 'Desain Komunikasi Visual',
            ],
            [
                'kode_jurusan' => 'APT',
                'nama_jurusan' => 'Agribisnis Pembibitan Tanaman',
            ],
            [
                'kode_jurusan' => 'ATPH',
                'nama_jurusan' => 'Agribisnis Tanaman Pangan dan Hortikultura',
            ],
        ];

        foreach ($jurusan as $item) {
            Jurusan::updateOrCreate(
                [
                    'id_sekolah' => $sekolah->id_sekolah,
                    'kode_jurusan' => $item['kode_jurusan'],
                ],
                [
                    'nama_jurusan' => $item['nama_jurusan'],
                    'status' => 'AKTIF',
                ]
            );
        }
    }
}
