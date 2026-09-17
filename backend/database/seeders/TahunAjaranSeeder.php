<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::where(
            'kode_sekolah',
            'SMKN8JEMBER'
        )->firstOrFail();

        TahunAjaran::updateOrCreate(
            [
                'id_sekolah' => $sekolah->id_sekolah,
                'nama_tahun_ajaran' => '2026/2027',
            ],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-12-31',
                'semester_aktif' => 'GANJIL',
                'status' => 'AKTIF',
            ]
        );
    }
}
