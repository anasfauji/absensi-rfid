<?php

namespace App\Services;

use App\Models\Kalender;
use App\Models\Kegiatan;
use Carbon\CarbonInterface;
use App\Models\PresensiGate;
use App\Models\PresensiKelas;

class AttendanceEvaluationService
{
    public function __construct(
        private StudentPlacementResolver $studentPlacementResolver
    ) {}
    public function evaluate(
        int $idSiswa,
        CarbonInterface $tanggal
    ): array {
        $wajibHadir = $this->isStudentRequiredToAttend(
            $idSiswa,
            $tanggal
        );

        if ($wajibHadir === null) {
            return [
                'status' => 'DATA_TIDAK_LENGKAP',
                'wajib_hadir' => null,
            ];
        }

        if ($wajibHadir === false) {
            return [
                'status' => 'TIDAK_BERLAKU',
                'wajib_hadir' => false,
            ];
        }

        if ($this->hasValidGateAttendance($idSiswa, $tanggal)) {
            return [
                'status' => 'HADIR',
                'wajib_hadir' => true,
            ];
        }

        if ($this->hasClassAttendanceEvidence($idSiswa, $tanggal)) {
            return [
                'status' => 'HADIR',
                'wajib_hadir' => true,
            ];
        }

        $nonAttendanceStatuses = $this->getDistinctNonAttendanceStatuses(
            $idSiswa,
            $tanggal
        );

        if (count($nonAttendanceStatuses) > 1) {
            return [
                'status' => null,
                'wajib_hadir' => true,
            ];
        }

        if (count($nonAttendanceStatuses) === 1) {
            return [
                'status' => $nonAttendanceStatuses[0],
                'wajib_hadir' => true,
            ];
        }


        if ($this->isAfterAttendanceCutoff($tanggal)) {
            return [
                'status' => 'ALPA',
                'wajib_hadir' => true,
            ];
        }

        return [
            'status' => null,
            'wajib_hadir' => true,
        ];
    }

    private function hasValidGateAttendance(
        int $idSiswa,
        CarbonInterface $tanggal
    ): bool {
        return PresensiGate::query()
            ->where('id_siswa', $idSiswa)
            ->whereDate('tanggal', $tanggal)
            ->exists();
    }

    private function hasClassAttendanceEvidence(
        int $idSiswa,
        CarbonInterface $tanggal
    ): bool {
        return PresensiKelas::query()
            ->where('id_siswa', $idSiswa)
            ->whereIn('status', ['HADIR', 'TERLAMBAT'])
            ->whereHas('sesiPresensi', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->exists();
    }

    private function hasClassAttendanceStatus(
        int $idSiswa,
        CarbonInterface $tanggal,
        string $status
    ): bool {
        return PresensiKelas::query()
            ->where('id_siswa', $idSiswa)
            ->where('status', $status)
            ->whereHas('sesiPresensi', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->exists();
    }

    private function getDistinctNonAttendanceStatuses(
        int $idSiswa,
        CarbonInterface $tanggal
    ): array {
        return PresensiKelas::query()
            ->where('id_siswa', $idSiswa)
            ->whereIn('status', [
                'SAKIT',
                'IZIN',
                'DISPENSASI',
            ])
            ->whereHas('sesiPresensi', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->distinct()
            ->pluck('status')
            ->values()
            ->all();
    }

    private function isAfterAttendanceCutoff(
        CarbonInterface $tanggal
    ): bool {
        $cutoff = $tanggal->copy()->setTime(23, 59, 59);

        return now()->greaterThanOrEqualTo($cutoff);
    }

    private function isStudentRequiredToAttend(
        int $idSiswa,
        CarbonInterface $tanggal
    ): ?bool {
        $kalender = Kalender::query()
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$kalender) {
            return false;
        }

        if ($kalender->status_hari !== 'AKTIF') {
            return false;
        }

        $penempatan = $this->studentPlacementResolver
            ->getEffectivePlacement($idSiswa, $tanggal);

        if (!$penempatan) {
            return null;
        }

        $idKelas = $penempatan->id_kelas;
        $idJurusan = $penempatan->kelas?->id_jurusan;

        $kegiatanAktif = Kegiatan::query()
            ->where('id_tahun_ajaran', $kalender->id_tahun_ajaran)
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->get();

        foreach ($kegiatanAktif as $kegiatan) {
            $mengenaiSiswa = $kegiatan->kegiatanSiswa()
                ->where('id_siswa', $idSiswa)
                ->exists();

            if ($mengenaiSiswa) {
                return false;
            }

            $mengenaiKelas = $kegiatan->kegiatanKelas()
                ->where('id_kelas', $idKelas)
                ->exists();

            if ($mengenaiKelas) {
                return false;
            }

            if ($idJurusan !== null) {
                $mengenaiJurusan = $kegiatan->kegiatanJurusan()
                    ->where('id_jurusan', $idJurusan)
                    ->exists();

                if ($mengenaiJurusan) {
                    return false;
                }
            }
        }

        return true;
    }
}
