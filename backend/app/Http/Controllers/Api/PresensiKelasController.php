<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UbahPresensiKelasRequest;
use Illuminate\Http\JsonResponse;
use App\Models\SesiPresensi;
use App\Services\SesiPresensiAuthorization;
use App\Models\PresensiKelas;


class PresensiKelasController extends Controller
{

    public function __construct(
        private SesiPresensiAuthorization $authorization
    ) {}

    public function update(
        UbahPresensiKelasRequest $request,
        int $id_sesi_presensi,
        int $id_siswa
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::findOrFail(
            $id_sesi_presensi
        );

        $bolehMengelola = $this->authorization
            ->canManageStudentAttendance(
                $request->user(),
                $sesiPresensi,
                $id_siswa
            );

        if (! $bolehMengelola) {
            abort(403);
        }

        $data = $request->validated();

        $presensiKelas = PresensiKelas::updateOrCreate(
            [
                'id_sesi_presensi' => $id_sesi_presensi,
                'id_siswa' => $id_siswa,
            ],
            [
                'status' => $data['status'],
                'waktu_presensi' => $data['waktu_presensi'] ?? null,
                'sumber' => $data['sumber'],
                'keterangan' => $data['keterangan'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Presensi kelas berhasil disimpan.',
            'data' => $presensiKelas,
        ], 200);
    }
}
