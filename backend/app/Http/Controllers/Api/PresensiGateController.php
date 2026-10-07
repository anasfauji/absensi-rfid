<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KoreksiPresensiGateRequest;
use App\Models\PresensiGate;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PresensiGateController extends Controller
{
    private const READ_COLUMNS = [
        'id_presensi_gate',
        'id_siswa',
        'tanggal',
        'waktu_masuk',
        'sumber_masuk',
        'keterangan',
        'dibuat_oleh',
        'diubah_oleh',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function index(): JsonResponse
    {
        $presensiGate = PresensiGate::query()
            ->select(self::READ_COLUMNS)
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_masuk')
            ->orderByDesc('id_presensi_gate')
            ->get();

        return response()->json([
            'message' => 'Daftar presensi gate berhasil diambil.',
            'data' => [
                'presensi_gate' => $presensiGate,
            ],
        ], 200);
    }

    public function show(int $id_presensi_gate): JsonResponse
    {
        $presensiGate = PresensiGate::query()
            ->select(self::READ_COLUMNS)
            ->findOrFail($id_presensi_gate);

        return response()->json([
            'message' => 'Detail presensi gate berhasil diambil.',
            'data' => [
                'presensi_gate' => $presensiGate,
            ],
        ], 200);
    }

    public function update(
        KoreksiPresensiGateRequest $request,
        int $id_presensi_gate
    ): JsonResponse {
        $presensiGate = PresensiGate::query()->findOrFail($id_presensi_gate);
        $data = $request->validated();

        $presensiGate = DB::transaction(function () use (
            $request,
            $presensiGate,
            $data
        ): PresensiGate {
            $dataSebelum = $this->snapshot($presensiGate);

            $presensiGate->fill($data);
            $presensiGate->diubah_oleh = $request->user()->id_pengguna;
            $presensiGate->save();
            $presensiGate->refresh();

            $this->auditLogService->catat(
                pengguna: $request->user(),
                aksi: 'KOREKSI',
                namaTabel: 'presensi_gate',
                idData: $presensiGate->id_presensi_gate,
                dataSebelum: $dataSebelum,
                dataSesudah: $this->snapshot($presensiGate),
                alasan: $data['keterangan'] ?? null,
            );

            return $presensiGate;
        });

        return response()->json([
            'message' => 'Evidence presensi gate berhasil dikoreksi.',
            'data' => $presensiGate,
        ], 200);
    }

    private function snapshot(PresensiGate $presensiGate): array
    {
        return [
            'id_presensi_gate' => $presensiGate->id_presensi_gate,
            'id_siswa' => $presensiGate->id_siswa,
            'tanggal' => $presensiGate->tanggal,
            'waktu_masuk' => $presensiGate->waktu_masuk,
            'sumber_masuk' => $presensiGate->sumber_masuk,
            'keterangan' => $presensiGate->keterangan,
            'dibuat_oleh' => $presensiGate->dibuat_oleh,
            'diubah_oleh' => $presensiGate->diubah_oleh,
        ];
    }
}
