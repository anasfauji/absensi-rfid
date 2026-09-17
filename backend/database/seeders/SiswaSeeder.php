<?php

namespace Database\Seeders;

use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $siswa = [
            [
                'nis' => '260001',
                'nama_siswa' => 'Ahmad Fauzan',
                'jenis_kelamin' => 'L',
                'status' => 'AKTIF',
            ],
            [
                'nis' => '260002',
                'nama_siswa' => 'Budi Santoso',
                'jenis_kelamin' => 'L',
                'status' => 'AKTIF',
            ],
            [
                'nis' => '260003',
                'nama_siswa' => 'Citra Lestari',
                'jenis_kelamin' => 'P',
                'status' => 'AKTIF',
            ],
            [
                'nis' => '260004',
                'nama_siswa' => 'Dimas Pratama',
                'jenis_kelamin' => 'L',
                'status' => 'AKTIF',
            ],
            [
                'nis' => '260005',
                'nama_siswa' => 'Eka Saputra',
                'jenis_kelamin' => 'L',
                'status' => 'AKTIF',
            ],
            [
                'nis' => '260006',
                'nama_siswa' => 'Fajar Maulana',
                'jenis_kelamin' => 'L',
                'status' => 'AKTIF',
            ],
        ];

        foreach ($siswa as $item) {
            Siswa::updateOrCreate(
                [
                    'nis' => $item['nis'],
                ],
                $item
            );
        }
    }
}
