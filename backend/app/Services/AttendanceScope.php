<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\SesiPresensi;
use Carbon\CarbonInterface;

class AttendanceScope
{
    public function __construct(
        private StudentPlacementResolver $studentPlacementResolver
    ) {}

    public function canViewStudentEvaluation(
        Pengguna $pengguna,
        int $idSiswa,
        CarbonInterface $tanggal
    ): bool {
        // 1. School-wide
        if ($pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
                'KS',
                'WAKA',
            ])
            ->exists()
        ) {
            return true;
        }

        // Ambil penempatan efektif siswa satu kali.
        $penempatan = $this->studentPlacementResolver
            ->getEffectivePlacement($idSiswa, $tanggal);

        // 2. KAPROG
        if ($pengguna->roles()
            ->where('kode_role', 'KAPROG')
            ->exists()
        ) {
            if ($penempatan !== null) {
                $idJurusanSiswa = $penempatan
                    ->kelas
                    ->jurusan
                    ->id_jurusan;

                $bolehSebagaiKaproG = $pengguna->penugasan()
                    ->whereHas('role', function ($query) {
                        $query->where('kode_role', 'KAPROG');
                    })
                    ->where('status', 'AKTIF')
                    ->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->where(function ($query) use ($tanggal) {
                        $query
                            ->whereNull('tanggal_selesai')
                            ->orWhereDate(
                                'tanggal_selesai',
                                '>=',
                                $tanggal
                            );
                    })
                    ->whereHas('penugasanJurusan', function ($query) use (
                        $idJurusanSiswa
                    ) {
                        $query->where(
                            'id_jurusan',
                            $idJurusanSiswa
                        );
                    })
                    ->exists();

                if ($bolehSebagaiKaproG) {
                    return true;
                }
            }
        }

        // 3. WALI KELAS
        if ($pengguna->roles()
            ->where('kode_role', 'WALI_KELAS')
            ->exists()
        ) {
            if ($penempatan !== null) {
                $idKelasSiswa = $penempatan->id_kelas;

                $bolehSebagaiWaliKelas = $pengguna->penugasan()
                    ->whereHas('role', function ($query) {
                        $query->where(
                            'kode_role',
                            'WALI_KELAS'
                        );
                    })
                    ->where('status', 'AKTIF')
                    ->whereDate(
                        'tanggal_mulai',
                        '<=',
                        $tanggal
                    )
                    ->where(function ($query) use ($tanggal) {
                        $query
                            ->whereNull('tanggal_selesai')
                            ->orWhereDate(
                                'tanggal_selesai',
                                '>=',
                                $tanggal
                            );
                    })
                    ->whereHas('penugasanKelas', function ($query) use (
                        $idKelasSiswa
                    ) {
                        $query->where(
                            'id_kelas',
                            $idKelasSiswa
                        );
                    })
                    ->exists();

                if ($bolehSebagaiWaliKelas) {
                    return true;
                }
            }
        }

        // 4. GURU
        if ($pengguna->roles()
            ->where('kode_role', 'GURU')
            ->exists()
        ) {
            if ($penempatan !== null && $pengguna->id_guru !== null) {
                $idKelasSiswa = $penempatan->id_kelas;

                $bolehSebagaiGuru = SesiPresensi::query()
                    ->where(
                        'id_guru',
                        $pengguna->id_guru
                    )
                    ->where(
                        'id_kelas',
                        $idKelasSiswa
                    )
                    ->whereDate(
                        'tanggal',
                        $tanggal
                    )
                    ->exists();

                if ($bolehSebagaiGuru) {
                    return true;
                }
            }
        }

        // 5. SISWA
        if ($pengguna->roles()
            ->where('kode_role', 'SISWA')
            ->exists()
        ) {
            if (
                $pengguna->id_siswa !== null
                && $pengguna->id_siswa === $idSiswa
            ) {
                return true;
            }
        }

        // Tidak ada role yang memberikan akses.
        return false;
    }
}