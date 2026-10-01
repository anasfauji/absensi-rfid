<?php

namespace App\Services;

use App\Models\PenempatanSiswa;
use Carbon\CarbonInterface;

class StudentPlacementResolver
{
    public function getEffectivePlacement(
        int $idSiswa,
        CarbonInterface $tanggal
    ): ?PenempatanSiswa {
        return PenempatanSiswa::query()
            ->with('kelas.jurusan')
            ->where('id_siswa', $idSiswa)
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($query) use ($tanggal) {
                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->first();
    }
}