<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin = Role::where('kode_role', 'ADMIN')->firstOrFail();

        $pengguna = Pengguna::updateOrCreate(
            [
                'username' => 'admin',
            ],
            [
                'password' => Hash::make('Admin123!'),
                'role' => 'ADMIN',
                'nama_tampilan' => 'Administrator',
                'email' => null,
                'status' => 'AKTIF',
            ]

        );

        Pengguna::updateOrCreate(
            ['username' => 'siswa'],
            [
                'password' => Hash::make('password'),
                'role' => 'SISWA',
                'nama_tampilan' => 'Siswa Test',
                'email' => null,
                'status' => 'AKTIF',
            ]
        );

        $pengguna->roles()->sync([
            $roleAdmin->id_role,
        ]);
    }
}
