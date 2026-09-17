<?php

namespace Database\Seeders;

use App\Models\IdentitasSekolah;
use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class IdentitasSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::where(
            'kode_sekolah',
            'SMKN8JEMBER'
        )->firstOrFail();

        IdentitasSekolah::updateOrCreate(
            [
                'id_sekolah' => $sekolah->id_sekolah,
                'berlaku_mulai' => '2008-09-08',
            ],
            [
                'nama_sekolah' => 'SMK Negeri 8 Jember',
                'alamat' => 'Jl. Pelita No.27',
                'kelurahan' => 'Sidomekar',
                'kecamatan' => 'Semboro',
                'kabupaten' => 'Jember',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '68157',
                'telepon' => '(0336) 444112',
                'email' => 'smknegeri08jember@gmail.com',
                'website' => 'smkn8jember.sch.id',
                'logo' => null,
                'berlaku_sampai' => null,
            ]
        );
    }
}
