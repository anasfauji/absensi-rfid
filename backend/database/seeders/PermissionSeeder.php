<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Sekolah & Identitas
            ['sekolah.lihat', 'Lihat Sekolah', 'sekolah', 'lihat'],
            ['sekolah.ubah', 'Ubah Sekolah', 'sekolah', 'ubah'],

            ['identitas_sekolah.lihat', 'Lihat Identitas Sekolah', 'identitas_sekolah', 'lihat'],
            ['identitas_sekolah.tambah', 'Tambah Identitas Sekolah', 'identitas_sekolah', 'tambah'],
            ['identitas_sekolah.ubah', 'Ubah Identitas Sekolah', 'identitas_sekolah', 'ubah'],

            // Tahun Ajaran
            ['tahun_ajaran.lihat', 'Lihat Tahun Ajaran', 'tahun_ajaran', 'lihat'],
            ['tahun_ajaran.tambah', 'Tambah Tahun Ajaran', 'tahun_ajaran', 'tambah'],
            ['tahun_ajaran.ubah', 'Ubah Tahun Ajaran', 'tahun_ajaran', 'ubah'],
            ['tahun_ajaran.aktifkan', 'Aktifkan Tahun Ajaran', 'tahun_ajaran', 'aktifkan'],

            // Jurusan & Kelas
            ['jurusan.lihat', 'Lihat Jurusan', 'jurusan', 'lihat'],
            ['jurusan.tambah', 'Tambah Jurusan', 'jurusan', 'tambah'],
            ['jurusan.ubah', 'Ubah Jurusan', 'jurusan', 'ubah'],
            ['jurusan.nonaktifkan', 'Nonaktifkan Jurusan', 'jurusan', 'nonaktifkan'],

            ['kelas.lihat', 'Lihat Kelas', 'kelas', 'lihat'],
            ['kelas.tambah', 'Tambah Kelas', 'kelas', 'tambah'],
            ['kelas.ubah', 'Ubah Kelas', 'kelas', 'ubah'],
            ['kelas.nonaktifkan', 'Nonaktifkan Kelas', 'kelas', 'nonaktifkan'],

            // Guru & Siswa
            ['guru.lihat', 'Lihat Guru', 'guru', 'lihat'],
            ['guru.tambah', 'Tambah Guru', 'guru', 'tambah'],
            ['guru.ubah', 'Ubah Guru', 'guru', 'ubah'],
            ['guru.nonaktifkan', 'Nonaktifkan Guru', 'guru', 'nonaktifkan'],

            ['siswa.lihat', 'Lihat Siswa', 'siswa', 'lihat'],
            ['siswa.tambah', 'Tambah Siswa', 'siswa', 'tambah'],
            ['siswa.ubah', 'Ubah Siswa', 'siswa', 'ubah'],
            ['siswa.nonaktifkan', 'Nonaktifkan Siswa', 'siswa', 'nonaktifkan'],

            // Penempatan Siswa
            ['penempatan_siswa.lihat', 'Lihat Penempatan Siswa', 'penempatan_siswa', 'lihat'],
            ['penempatan_siswa.tambah', 'Tambah Penempatan Siswa', 'penempatan_siswa', 'tambah'],
            ['penempatan_siswa.ubah', 'Ubah Penempatan Siswa', 'penempatan_siswa', 'ubah'],
            ['penempatan_siswa.batalkan', 'Batalkan Penempatan Siswa', 'penempatan_siswa', 'batalkan'],

            // Pengguna & Authorization
            ['pengguna.lihat', 'Lihat Pengguna', 'pengguna', 'lihat'],
            ['pengguna.tambah', 'Tambah Pengguna', 'pengguna', 'tambah'],
            ['pengguna.ubah', 'Ubah Pengguna', 'pengguna', 'ubah'],
            ['pengguna.nonaktifkan', 'Nonaktifkan Pengguna', 'pengguna', 'nonaktifkan'],

            ['role.lihat', 'Lihat Role', 'role', 'lihat'],
            ['role.tambah', 'Tambah Role', 'role', 'tambah'],
            ['role.ubah', 'Ubah Role', 'role', 'ubah'],
            ['role.nonaktifkan', 'Nonaktifkan Role', 'role', 'nonaktifkan'],

            ['permission.lihat', 'Lihat Permission', 'permission', 'lihat'],
            ['permission.tambah', 'Tambah Permission', 'permission', 'tambah'],
            ['permission.ubah', 'Ubah Permission', 'permission', 'ubah'],
            ['permission.nonaktifkan', 'Nonaktifkan Permission', 'permission', 'nonaktifkan'],

            ['penugasan.lihat', 'Lihat Penugasan', 'penugasan', 'lihat'],
            ['penugasan.tambah', 'Tambah Penugasan', 'penugasan', 'tambah'],
            ['penugasan.ubah', 'Ubah Penugasan', 'penugasan', 'ubah'],
            ['penugasan.selesaikan', 'Selesaikan Penugasan', 'penugasan', 'selesaikan'],

            // Pembelajaran
            ['mata_pelajaran.lihat', 'Lihat Mata Pelajaran', 'mata_pelajaran', 'lihat'],
            ['mata_pelajaran.tambah', 'Tambah Mata Pelajaran', 'mata_pelajaran', 'tambah'],
            ['mata_pelajaran.ubah', 'Ubah Mata Pelajaran', 'mata_pelajaran', 'ubah'],
            ['mata_pelajaran.nonaktifkan', 'Nonaktifkan Mata Pelajaran', 'mata_pelajaran', 'nonaktifkan'],

            ['pengajaran.lihat', 'Lihat Pengajaran', 'pengajaran', 'lihat'],
            ['pengajaran.tambah', 'Tambah Pengajaran', 'pengajaran', 'tambah'],
            ['pengajaran.ubah', 'Ubah Pengajaran', 'pengajaran', 'ubah'],
            ['pengajaran.selesaikan', 'Selesaikan Pengajaran', 'pengajaran', 'selesaikan'],

            ['wali_kelas.lihat', 'Lihat Wali Kelas', 'wali_kelas', 'lihat'],
            ['wali_kelas.tambah', 'Tambah Wali Kelas', 'wali_kelas', 'tambah'],
            ['wali_kelas.ubah', 'Ubah Wali Kelas', 'wali_kelas', 'ubah'],
            ['wali_kelas.selesaikan', 'Selesaikan Wali Kelas', 'wali_kelas', 'selesaikan'],

            // Kalender & Kegiatan
            ['kalender.lihat', 'Lihat Kalender', 'kalender', 'lihat'],
            ['kalender.tambah', 'Tambah Kalender', 'kalender', 'tambah'],
            ['kalender.ubah', 'Ubah Kalender', 'kalender', 'ubah'],
            ['kalender.aktifkan', 'Aktifkan Hari', 'kalender', 'aktifkan'],
            ['kalender.nonaktifkan', 'Nonaktifkan Hari', 'kalender', 'nonaktifkan'],

            ['kegiatan.lihat', 'Lihat Kegiatan', 'kegiatan', 'lihat'],
            ['kegiatan.tambah', 'Tambah Kegiatan', 'kegiatan', 'tambah'],
            ['kegiatan.ubah', 'Ubah Kegiatan', 'kegiatan', 'ubah'],
            ['kegiatan.aktifkan', 'Aktifkan Kegiatan', 'kegiatan', 'aktifkan'],
            ['kegiatan.selesaikan', 'Selesaikan Kegiatan', 'kegiatan', 'selesaikan'],
            ['kegiatan.batalkan', 'Batalkan Kegiatan', 'kegiatan', 'batalkan'],

            ['pengecualian_kewajiban.lihat', 'Lihat Pengecualian Kewajiban', 'pengecualian_kewajiban', 'lihat'],
            ['pengecualian_kewajiban.tambah', 'Tambah Pengecualian Kewajiban', 'pengecualian_kewajiban', 'tambah'],
            ['pengecualian_kewajiban.ubah', 'Ubah Pengecualian Kewajiban', 'pengecualian_kewajiban', 'ubah'],
            ['pengecualian_kewajiban.batalkan', 'Batalkan Pengecualian Kewajiban', 'pengecualian_kewajiban', 'batalkan'],

            // RFID
            ['kartu_rfid.lihat', 'Lihat Kartu RFID', 'kartu_rfid', 'lihat'],
            ['kartu_rfid.tambah', 'Tambah Kartu RFID', 'kartu_rfid', 'tambah'],
            ['kartu_rfid.ubah', 'Ubah Kartu RFID', 'kartu_rfid', 'ubah'],
            ['kartu_rfid.nonaktifkan', 'Nonaktifkan Kartu RFID', 'kartu_rfid', 'nonaktifkan'],

            ['perangkat_rfid.lihat', 'Lihat Perangkat RFID', 'perangkat_rfid', 'lihat'],
            ['perangkat_rfid.tambah', 'Tambah Perangkat RFID', 'perangkat_rfid', 'tambah'],
            ['perangkat_rfid.ubah', 'Ubah Perangkat RFID', 'perangkat_rfid', 'ubah'],
            ['perangkat_rfid.nonaktifkan', 'Nonaktifkan Perangkat RFID', 'perangkat_rfid', 'nonaktifkan'],

            ['rfid_event.lihat', 'Lihat RFID Event', 'rfid_event', 'lihat'],

            // Presensi Gate
            ['presensi_gate.lihat', 'Lihat Presensi Gate', 'presensi_gate', 'lihat'],
            ['presensi_gate.koreksi', 'Koreksi Presensi Gate', 'presensi_gate', 'koreksi'],

            // Presensi Kelas
            ['sesi_presensi.lihat', 'Lihat Sesi Presensi', 'sesi_presensi', 'lihat'],
            ['sesi_presensi.tambah', 'Tambah Sesi Presensi', 'sesi_presensi', 'tambah'],
            ['sesi_presensi.ubah', 'Ubah Sesi Presensi', 'sesi_presensi', 'ubah'],
            ['sesi_presensi.tutup', 'Tutup Sesi Presensi', 'sesi_presensi', 'tutup'],

            ['presensi_kelas.lihat', 'Lihat Presensi Kelas', 'presensi_kelas', 'lihat'],
            ['presensi_kelas.koreksi', 'Koreksi Presensi Kelas', 'presensi_kelas', 'koreksi'],

            // Sistem
            ['audit_log.lihat', 'Lihat Audit Log', 'audit_log', 'lihat'],

            ['notifikasi.lihat', 'Lihat Notifikasi', 'notifikasi', 'lihat'],
            ['notifikasi.tandai_dibaca', 'Tandai Notifikasi Dibaca', 'notifikasi', 'tandai_dibaca'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'kode_permission' => $permission[0],
                ],
                [
                    'nama_permission' => $permission[1],
                    'modul' => $permission[2],
                    'aksi' => $permission[3],
                    'deskripsi' => null,
                    'status' => 'AKTIF',
                ]
            );
        }
    }
}
