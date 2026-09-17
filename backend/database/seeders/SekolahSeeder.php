<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class SekolahSeeder extends Seeder
{
    public function run(): void
    {
        Sekolah::updateOrCreate(
            [
                'kode_sekolah' => 'SMKN8JEMBER',
            ],
            [
                'npsn' => '20554690',
                'status' => 'AKTIF',
            ]
        );
    }
}
