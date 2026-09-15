<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'kode_role' => 'ADMIN',
                'nama_role' => 'Administrator',
                'deskripsi' => 'Mengelola konfigurasi dan seluruh sistem.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'OPERATOR',
                'nama_role' => 'Operator',
                'deskripsi' => 'Mengelola operasional administrasi sistem.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'KS',
                'nama_role' => 'Kepala Sekolah',
                'deskripsi' => 'Memantau data dan informasi sekolah secara menyeluruh.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'WAKA',
                'nama_role' => 'Wakil Kepala Sekolah',
                'deskripsi' => 'Mengelola dan memantau data sesuai bidang tugas.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'KAPROG',
                'nama_role' => 'Kepala Program Keahlian',
                'deskripsi' => 'Mengelola dan memantau data dalam lingkup program keahlian.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'BK',
                'nama_role' => 'Bimbingan Konseling',
                'deskripsi' => 'Mengelola dan memantau data siswa dalam lingkup bimbingan konseling.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'WALI_KELAS',
                'nama_role' => 'Wali Kelas',
                'deskripsi' => 'Mengelola dan memantau data siswa dalam kelas yang menjadi tanggung jawabnya.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'GURU',
                'nama_role' => 'Guru',
                'deskripsi' => 'Melaksanakan kegiatan pembelajaran dan mengelola presensi kelas.',
                'status' => 'AKTIF',
            ],
            [
                'kode_role' => 'SISWA',
                'nama_role' => 'Siswa',
                'deskripsi' => 'Melihat data dan presensi dirinya sendiri.',
                'status' => 'AKTIF',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                [
                    'kode_role' => $role['kode_role'],
                ],
                $role
            );
        }
    }
}
