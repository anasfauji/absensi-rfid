<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\MataPelajaran::updateOrCreate(
            [
                'kode_mata_pelajaran' => 'RPL-DASAR',
            ],
            [
                'nama_mata_pelajaran' => 'Dasar-Dasar Rekayasa Perangkat Lunak',
                'kelompok' => 'KEJURUAN',
                'status' => 'AKTIF',
                'keterangan' => 'Data dasar untuk kebutuhan pengembangan dan pengujian ELIES.',
            ]
        );
    }
}
