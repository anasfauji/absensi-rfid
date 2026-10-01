<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Pengajaran;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use App\Models\SesiPresensi;
use App\Models\Guru;
use App\Services\StudentPlacementResolver;

class SesiPresensiAuthorization
{
    public function __construct(
        private StudentPlacementResolver $studentPlacementResolver
    ) {}

    public function canCreate(
        Pengguna $pengguna,
        int $idKelas,
        int $idMataPelajaran,
        CarbonInterface $tanggal
    ): bool {
        if ($pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists()
        ) {
            return true;
        }

        if (! $pengguna->roles()
            ->where('kode_role', 'GURU')
            ->exists()) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        if (! Carbon::today()->isSameDay($tanggal)) {
            return false;
        }

        return Pengajaran::query()
            ->where('id_guru', $pengguna->id_guru)
            ->where('id_kelas', $idKelas)
            ->where('id_mata_pelajaran', $idMataPelajaran)
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
            ->exists();
    }

    public function canManageAttendance(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        if ($pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists()
        ) {
            return true;
        }

        if (! $pengguna->roles()
            ->where('kode_role', 'GURU')
            ->exists()) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        return $sesiPresensi->id_guru === $pengguna->id_guru
            || $sesiPresensi->id_guru_penangan === $pengguna->id_guru;
    }

    public function canUpdateSession(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        if ($pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists()
        ) {
            return true;
        }

        if (! $pengguna->roles()
            ->where('kode_role', 'GURU')
            ->exists()) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        return $sesiPresensi->id_guru === $pengguna->id_guru;
    }

    public function canClose(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        if ($pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists()
        ) {
            return true;
        }

        if (! $pengguna->roles()
            ->where('kode_role', 'GURU')
            ->exists()) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        return $sesiPresensi->id_guru === $pengguna->id_guru
            || $sesiPresensi->id_guru_penangan === $pengguna->id_guru;
    }

    public function canAssignHandler(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        return $pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists();
    }

    public function isValidHandler(
        Guru $guru
    ): bool {
        return $guru->status === 'AKTIF';
    }

    public function canManageStudentAttendance(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi,
        int $idSiswa
    ): bool {
        if (! $this->canManageAttendance($pengguna, $sesiPresensi)) {
            return false;
        }

        $penempatan = $this->studentPlacementResolver
            ->getEffectivePlacement(
                $idSiswa,
                Carbon::parse($sesiPresensi->tanggal)
            );

        if ($penempatan === null) {
            return false;
        }

        return $penempatan->id_kelas === $sesiPresensi->id_kelas;
    }
}
