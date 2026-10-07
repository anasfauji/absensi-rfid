<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::pluck('id_permission', 'kode_permission');

        $roles = Role::pluck('id_role', 'kode_role');

        $rolePermissions = [

            /*
            |--------------------------------------------------------------------------
            | ADMIN
            |--------------------------------------------------------------------------
            | Admin memiliki seluruh permission sistem.
            */
            'ADMIN' => Permission::pluck('kode_permission')->toArray(),

            /*
            |--------------------------------------------------------------------------
            | OPERATOR
            |--------------------------------------------------------------------------
            */
            'OPERATOR' => [
                'sekolah.lihat',
                'sekolah.ubah',

                'identitas_sekolah.lihat',
                'identitas_sekolah.tambah',
                'identitas_sekolah.ubah',

                'tahun_ajaran.lihat',
                'tahun_ajaran.tambah',
                'tahun_ajaran.ubah',
                'tahun_ajaran.aktifkan',

                'jurusan.lihat',
                'jurusan.tambah',
                'jurusan.ubah',
                'jurusan.nonaktifkan',

                'kelas.lihat',
                'kelas.tambah',
                'kelas.ubah',
                'kelas.nonaktifkan',

                'guru.lihat',
                'guru.tambah',
                'guru.ubah',
                'guru.nonaktifkan',

                'siswa.lihat',
                'siswa.tambah',
                'siswa.ubah',
                'siswa.nonaktifkan',

                'penempatan_siswa.lihat',
                'penempatan_siswa.tambah',
                'penempatan_siswa.ubah',
                'penempatan_siswa.batalkan',

                'pengguna.lihat',
                'pengguna.tambah',
                'pengguna.ubah',
                'pengguna.nonaktifkan',

                'penugasan.lihat',
                'penugasan.tambah',
                'penugasan.ubah',
                'penugasan.selesaikan',

                'mata_pelajaran.lihat',
                'mata_pelajaran.tambah',
                'mata_pelajaran.ubah',
                'mata_pelajaran.nonaktifkan',

                'pengajaran.lihat',
                'pengajaran.tambah',
                'pengajaran.ubah',
                'pengajaran.selesaikan',

                'wali_kelas.lihat',
                'wali_kelas.tambah',
                'wali_kelas.ubah',
                'wali_kelas.selesaikan',

                'kalender.lihat',
                'kalender.tambah',
                'kalender.ubah',
                'kalender.aktifkan',
                'kalender.nonaktifkan',

                'kegiatan.lihat',
                'kegiatan.tambah',
                'kegiatan.ubah',
                'kegiatan.aktifkan',
                'kegiatan.selesaikan',
                'kegiatan.batalkan',

                'pengecualian_kewajiban.lihat',
                'pengecualian_kewajiban.tambah',
                'pengecualian_kewajiban.ubah',
                'pengecualian_kewajiban.batalkan',

                'kartu_rfid.lihat',
                'kartu_rfid.tambah',
                'kartu_rfid.ubah',
                'kartu_rfid.nonaktifkan',

                'perangkat_rfid.lihat',
                'perangkat_rfid.tambah',
                'perangkat_rfid.ubah',
                'perangkat_rfid.nonaktifkan',

                'rfid_event.lihat',

                'presensi_gate.lihat',
                'presensi_gate.koreksi',

                'sesi_presensi.lihat',
                'sesi_presensi.tambah',
                'sesi_presensi.ubah',
                'sesi_presensi.tutup',

                'presensi_kelas.lihat',
                'presensi_kelas.koreksi',

                'audit_log.lihat',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | KS
            |--------------------------------------------------------------------------
            */
            'KS' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',
                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',
                'penempatan_siswa.lihat',
                'penugasan.lihat',
                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'wali_kelas.lihat',
                'kalender.lihat',
                'kegiatan.lihat',
                'pengecualian_kewajiban.lihat',
                'kartu_rfid.lihat',
                'perangkat_rfid.lihat',
                'rfid_event.lihat',
                'presensi_gate.lihat',
                'sesi_presensi.lihat',
                'presensi_kelas.lihat',
                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | WAKA
            |--------------------------------------------------------------------------
            */
            'WAKA' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',

                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',
                'penempatan_siswa.lihat',
                'penugasan.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'pengajaran.tambah',
                'pengajaran.ubah',
                'pengajaran.selesaikan',

                'wali_kelas.lihat',
                'wali_kelas.tambah',
                'wali_kelas.ubah',
                'wali_kelas.selesaikan',

                'kalender.lihat',
                'kalender.tambah',
                'kalender.ubah',
                'kalender.aktifkan',
                'kalender.nonaktifkan',

                'kegiatan.lihat',
                'kegiatan.tambah',
                'kegiatan.ubah',
                'kegiatan.aktifkan',
                'kegiatan.selesaikan',
                'kegiatan.batalkan',

                'pengecualian_kewajiban.lihat',
                'pengecualian_kewajiban.tambah',
                'pengecualian_kewajiban.ubah',
                'pengecualian_kewajiban.batalkan',

                'kartu_rfid.lihat',
                'perangkat_rfid.lihat',
                'rfid_event.lihat',
                'presensi_gate.lihat',
                'sesi_presensi.lihat',
                'presensi_kelas.lihat',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | KAPROG
            |--------------------------------------------------------------------------
            */
            'KAPROG' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',
                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',
                'penempatan_siswa.lihat',
                'penugasan.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'pengajaran.tambah',
                'pengajaran.ubah',
                'pengajaran.selesaikan',

                'wali_kelas.lihat',

                'kalender.lihat',

                'kegiatan.lihat',
                'kegiatan.tambah',
                'kegiatan.ubah',
                'kegiatan.aktifkan',
                'kegiatan.selesaikan',
                'kegiatan.batalkan',

                'pengecualian_kewajiban.lihat',
                'pengecualian_kewajiban.tambah',
                'pengecualian_kewajiban.ubah',
                'pengecualian_kewajiban.batalkan',

                'kartu_rfid.lihat',
                'rfid_event.lihat',
                'presensi_gate.lihat',
                'sesi_presensi.lihat',
                'presensi_kelas.lihat',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | BK
            |--------------------------------------------------------------------------
            */
            'BK' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',
                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',
                'penempatan_siswa.lihat',
                'penugasan.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'wali_kelas.lihat',

                'kalender.lihat',
                'kegiatan.lihat',

                'pengecualian_kewajiban.lihat',
                'pengecualian_kewajiban.tambah',
                'pengecualian_kewajiban.ubah',
                'pengecualian_kewajiban.batalkan',

                'kartu_rfid.lihat',
                'rfid_event.lihat',

                'presensi_gate.lihat',

                'sesi_presensi.lihat',
                'presensi_kelas.lihat',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | WALI KELAS
            |--------------------------------------------------------------------------
            */
            'WALI_KELAS' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',

                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',
                'penempatan_siswa.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'wali_kelas.lihat',

                'kalender.lihat',
                'kegiatan.lihat',

                'pengecualian_kewajiban.lihat',
                'pengecualian_kewajiban.tambah',
                'pengecualian_kewajiban.ubah',
                'pengecualian_kewajiban.batalkan',

                'kartu_rfid.lihat',
                'rfid_event.lihat',

                'presensi_gate.lihat',

                'sesi_presensi.lihat',
                'presensi_kelas.lihat',
                'presensi_kelas.koreksi',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | GURU
            |--------------------------------------------------------------------------
            */
            'GURU' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',

                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',

                'wali_kelas.lihat',

                'kalender.lihat',
                'kegiatan.lihat',

                'kartu_rfid.lihat',
                'rfid_event.lihat',

                'presensi_gate.lihat',

                'sesi_presensi.lihat',
                'sesi_presensi.tambah',
                'sesi_presensi.ubah',
                'sesi_presensi.tutup',

                'presensi_kelas.lihat',
                'presensi_kelas.koreksi',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],

            /*
            |--------------------------------------------------------------------------
            | SISWA
            |--------------------------------------------------------------------------
            */
            'SISWA' => [
                'sekolah.lihat',
                'identitas_sekolah.lihat',
                'tahun_ajaran.lihat',

                'jurusan.lihat',
                'kelas.lihat',
                'guru.lihat',
                'siswa.lihat',

                'mata_pelajaran.lihat',
                'pengajaran.lihat',
                'wali_kelas.lihat',

                'kalender.lihat',
                'kegiatan.lihat',

                'kartu_rfid.lihat',

                'presensi_gate.lihat',

                'sesi_presensi.lihat',
                'presensi_kelas.lihat',

                'notifikasi.lihat',
                'notifikasi.tandai_dibaca',
            ],
        ];

        DB::transaction(function () use (
            $rolePermissions,
            $roles,
            $permissions
        ) {
            foreach ($rolePermissions as $roleCode => $permissionCodes) {

                $roleId = $roles[$roleCode];

                $permissionIds = collect($permissionCodes)
                    ->map(fn ($permissionCode) => $permissions[$permissionCode])
                    ->filter()
                    ->unique()
                    ->values();

                $role = Role::findOrFail($roleId);

                $role->permissions()->sync($permissionIds);
            }
        });
    }
}
