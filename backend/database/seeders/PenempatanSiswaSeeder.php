<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\PenempatanSiswa;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class PenempatanSiswaSeeder extends Seeder
{
    public function run(): void
    {
        $penempatan = [
            [
                'nis' => '260001',
                'nama_kelas' => 'X RPL 1',
            ],
            [
                'nis' => '260002',
                'nama_kelas' => 'X RPL 2',
            ],
            [
                'nis' => '260003',
                'nama_kelas' => 'XI RPL 1',
            ],
            [
                'nis' => '260004',
                'nama_kelas' => 'XI RPL 2',
            ],
            [
                'nis' => '260005',
                'nama_kelas' => 'XII RPL 1',
            ],
            [
                'nis' => '260006',
                'nama_kelas' => 'XII RPL 2',
            ],
        ];

        foreach ($penempatan as $item) {
            $siswa = Siswa::where(
                'nis',
                $item['nis']
            )->firstOrFail();

            $kelas = Kelas::where(
                'nama_kelas',
                $item['nama_kelas']
            )
                ->where('status', 'AKTIF')
                ->firstOrFail();

            PenempatanSiswa::updateOrCreate(
                [
                    'id_siswa' => $siswa->id_siswa,
                    'id_kelas' => $kelas->id_kelas,
                    'tanggal_mulai' => '2026-07-01',
                ],
                [
                    'tanggal_selesai' => null,
                    'status' => 'AKTIF',
                    'keterangan' => 'Data dummy untuk pengembangan sistem absensi RFID.',
                ]
            );
        }
    }
}
