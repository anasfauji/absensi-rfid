<?php

namespace Database\Seeders;

use App\Models\Guru;
use Illuminate\Database\Seeder;

class GuruSeeder extends Seeder
{
    public function run(): void
    {
        Guru::updateOrCreate(
            [
                'nip' => '197804302011011006',
            ],
            [
                'nama_guru' => 'Kukuh Suprapto, S.Kom',
                'jenis_kelamin' => 'L',
                'email' => null,
                'nomor_telepon' => null,
                'foto' => null,
                'status' => 'AKTIF',
            ]
        );
    }
}