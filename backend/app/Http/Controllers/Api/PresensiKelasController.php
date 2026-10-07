<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UbahPresensiKelasRequest;
use Illuminate\Http\JsonResponse;
use App\Models\SesiPresensi;
use App\Services\SesiPresensiAuthorization;
use App\Models\PresensiKelas;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class PresensiKelasController extends Controller
{
    public function __construct(
        private SesiPresensiAuthorization $authorization,
        private AuditLogService $auditLogService
    ) {}

    public function update(
        UbahPresensiKelasRequest $request,
        int $id_sesi_presensi,
        int $id_siswa
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::findOrFail(
            $id_sesi_presensi
        );

        $hasilValidasi = $this->authorization
            ->validateStudentAttendanceManagement(
                $request->user(),
                $sesiPresensi,
                $id_siswa
            );

        if (
            $hasilValidasi === 'ACTOR_UNAUTHORIZED'
            || $hasilValidasi === 'ACTOR_OUTSIDE_SCOPE'
        ) {
            abort(403);
        }

        if ($hasilValidasi === 'STUDENT_OUTSIDE_SESSION_CLASS') {
            abort(422);
        }

        if (
            $sesiPresensi->status_sesi !== 'AKTIF'
        ) {
            abort(422);
        }

        $data = $request->validated();

        $presensiKelas = DB::transaction(function () use (
            $request,
            $id_sesi_presensi,
            $id_siswa,
            $data
        ) {
            $presensiKelas = PresensiKelas::query()
                ->where('id_sesi_presensi', $id_sesi_presensi)
                ->where('id_siswa', $id_siswa)
                ->first();

            if ($presensiKelas === null) {
                $presensiKelas = PresensiKelas::create([
                    'id_sesi_presensi' => $id_sesi_presensi,
                    'id_siswa' => $id_siswa,
                    'status' => $data['status'],
                    'waktu_presensi' => $data['waktu_presensi'] ?? null,
                    'sumber' => $data['sumber'],
                    'keterangan' => $data['keterangan'] ?? null,
                ]);

                $this->auditLogService->catat(
                    pengguna: $request->user(),
                    aksi: 'CREATE',
                    namaTabel: 'presensi_kelas',
                    idData: $presensiKelas->id_presensi_kelas,
                    dataSebelum: null,
                    dataSesudah: $this->snapshotPresensi($presensiKelas),
                );

                return $presensiKelas;
            }

            $dataSebelum = $this->snapshotPresensi($presensiKelas);

            $presensiKelas->update([
                'status' => $data['status'],
                'waktu_presensi' => $data['waktu_presensi'] ?? null,
                'sumber' => $data['sumber'],
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $presensiKelas->refresh();

            $this->auditLogService->catat(
                pengguna: $request->user(),
                aksi: 'KOREKSI',
                namaTabel: 'presensi_kelas',
                idData: $presensiKelas->id_presensi_kelas,
                dataSebelum: $dataSebelum,
                dataSesudah: $this->snapshotPresensi($presensiKelas),
                alasan: $data['keterangan'] ?? null,
            );

            return $presensiKelas;
        });

        return response()->json([
            'message' => 'Presensi kelas berhasil disimpan.',
            'data' => $presensiKelas,
        ], 200);
    }


    //----------Helpers----------//

    private function snapshotPresensi(PresensiKelas $presensiKelas): array
    {
        return [
            'id_sesi_presensi' => $presensiKelas->id_sesi_presensi,
            'id_siswa' => $presensiKelas->id_siswa,
            'status' => $presensiKelas->status,
            'waktu_presensi' => $presensiKelas->waktu_presensi,
            'sumber' => $presensiKelas->sumber,
            'keterangan' => $presensiKelas->keterangan,
        ];
    }
}
