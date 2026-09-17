<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin = Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $roleSiswa = Role::where(
            'kode_role',
            'SISWA'
        )->firstOrFail();

        // =========================
        // AKUN ADMIN
        // =========================

        $penggunaAdmin = Pengguna::updateOrCreate(
            [
                'username' => 'admin',
            ],
            [
                'password' => Hash::make('Admin123!'),
                'nama_tampilan' => 'Administrator',
                'email' => null,
                'status' => 'AKTIF',
            ]
        );

        $penggunaAdmin->roles()->sync([
            $roleAdmin->id_role,
        ]);

        // =========================
        // AKUN SISWA TEST LAMA
        // =========================

        $penggunaSiswaTest = Pengguna::updateOrCreate(
            [
                'username' => 'siswa',
            ],
            [
                'password' => Hash::make('Siswa123!'),
                'nama_tampilan' => 'Siswa Test',
                'email' => null,
                'status' => 'AKTIF',
            ]
        );

        $penggunaSiswaTest->roles()->sync([
            $roleSiswa->id_role,
        ]);

        // =========================
        // AKUN 6 SISWA DUMMY
        // =========================

        $siswa = Siswa::orderBy('id_siswa')->get();

        foreach ($siswa as $index => $dataSiswa) {
            $nomor = $index + 1;

            $pengguna = Pengguna::updateOrCreate(
                [
                    'username' => 'siswa' . str_pad(
                        $nomor,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ),
                ],
                [
                    'password' => Hash::make('Siswa123!'),
                    'nama_tampilan' => $dataSiswa->nama_siswa,
                    'email' => null,
                    'id_siswa' => $dataSiswa->id_siswa,
                    'status' => 'AKTIF',
                ]
            );

            $pengguna->roles()->sync([
                $roleSiswa->id_role,
            ]);
        }
    }
}