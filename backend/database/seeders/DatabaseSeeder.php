<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SekolahSeeder::class,
            IdentitasSekolahSeeder::class,

            TahunAjaranSeeder::class,
            JurusanSeeder::class,
            KelasSeeder::class,

            GuruSeeder::class,
            SiswaSeeder::class,
            PenempatanSiswaSeeder::class,
            MataPelajaranSeeder::class,
            PengajaranSeeder::class,

            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,

            PenggunaSeeder::class,
        ]);
    }
}
