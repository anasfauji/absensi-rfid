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

        if ($this->hasActiveHomeroomScope($pengguna, $sesiPresensi)) {
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

    public function validateStudentAttendanceManagement(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi,
        int $idSiswa
    ): ?string {
        if (! $this->canManageAttendance($pengguna, $sesiPresensi)) {
            return 'ACTOR_UNAUTHORIZED';
        }

        $penempatan = $this->studentPlacementResolver
            ->getEffectivePlacement(
                $idSiswa,
                Carbon::parse($sesiPresensi->tanggal)
            );

        if (
            $penempatan === null
            || $penempatan->id_kelas !== $sesiPresensi->id_kelas
        ) {
            $isGuruPenangan =
                $pengguna->id_guru !== null
                && $sesiPresensi->id_guru_penangan === $pengguna->id_guru;

            if ($isGuruPenangan) {
                return 'ACTOR_OUTSIDE_SCOPE';
            }

            return 'STUDENT_OUTSIDE_SESSION_CLASS';
        }

        return null;
    }

    public function canActivate(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        if (
            $pengguna->roles()
            ->whereIn('kode_role', ['ADMIN', 'OPERATOR'])
            ->exists()
        ) {
            return true;
        }

        if (
            ! $pengguna->roles()
                ->where('kode_role', 'GURU')
                ->exists()
        ) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        return $sesiPresensi->id_guru_penangan === $pengguna->id_guru;
    }

    public function canViewSession(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        // ADMIN dan OPERATOR dapat melihat seluruh sesi.
        if (
            $pengguna->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists()
        ) {
            return true;
        }

        if ($this->hasActiveHomeroomScope($pengguna, $sesiPresensi)) {
            return true;
        }

        // Guru harus memiliki identitas Guru.
        if (
            ! $pengguna->roles()
                ->where('kode_role', 'GURU')
                ->exists()
        ) {
            return false;
        }

        if ($pengguna->id_guru === null) {
            return false;
        }

        // Guru Pemilik atau Guru Penangan dapat melihat sesi.
        return $sesiPresensi->id_guru === $pengguna->id_guru
            || $sesiPresensi->id_guru_penangan === $pengguna->id_guru;
    }

    private function hasActiveHomeroomScope(
        Pengguna $pengguna,
        SesiPresensi $sesiPresensi
    ): bool {
        if (! $pengguna->roles()
            ->where('kode_role', 'WALI_KELAS')
            ->exists()) {
            return false;
        }

        $tanggalSesi = Carbon::parse($sesiPresensi->tanggal);

        return $pengguna->penugasan()
            ->whereHas('role', function ($query) {
                $query->where('kode_role', 'WALI_KELAS');
            })
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggalSesi)
            ->where(function ($query) use ($tanggalSesi) {
                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggalSesi);
            })
            ->whereHas('penugasanKelas', function ($query) use ($sesiPresensi) {
                $query->where('id_kelas', $sesiPresensi->id_kelas);
            })
            ->exists();
    }
}
