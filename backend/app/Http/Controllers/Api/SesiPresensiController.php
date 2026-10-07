<?php

namespace App\Http\Controllers\Api;

use App\Models\SesiPresensi;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuatSesiPresensiRequest;
use App\Http\Requests\UbahSesiPresensiRequest;
use App\Http\Requests\DelegasiGuruPenanganRequest;
use App\Services\SesiPresensiAuthorization;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SesiPresensiController extends Controller
{
    public function __construct(
        private SesiPresensiAuthorization $authorization
    ) {}

    public function store(
        BuatSesiPresensiRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $pengguna = $request->user();

        $tanggal = Carbon::parse($data['tanggal']);

        $bolehMembuat = $this->authorization->canCreate(
            $pengguna,
            (int) $data['id_kelas'],
            (int) $data['id_mata_pelajaran'],
            $tanggal
        );

        if (! $bolehMembuat) {
            abort(403);
        }

        $isAdminOrOperator = $pengguna
            ->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists();

        $idGuru = $isAdminOrOperator
            ? $data['id_guru']
            : $pengguna->id_guru;

        $idGuruPenangan = $isAdminOrOperator
            ? $data['id_guru_penangan']
            : null;

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $idGuru,
            'id_guru_penangan' => $idGuruPenangan,
            'id_kelas' => $data['id_kelas'],
            'id_mata_pelajaran' => $data['id_mata_pelajaran'],
            'tanggal' => $data['tanggal'],
            'waktu_mulai' => $data['waktu_mulai'],
            'waktu_selesai' => $data['waktu_selesai'] ?? null,
            'status_sesi' => $isAdminOrOperator ? 'DRAFT' : 'AKTIF',
            'materi' => $data['materi'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Sesi presensi berhasil dibuat.',
            'data' => $sesiPresensi,
        ], 201);
    }

    public function update(
        UbahSesiPresensiRequest $request,
        int $id_sesi_presensi
    ): JsonResponse {
        $data = $request->validated();

        $sesiPresensi = SesiPresensi::findOrFail($id_sesi_presensi);
        $pengguna = $request->user();

        if (! $this->authorization->canUpdateSession(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        if ($sesiPresensi->status_sesi === 'DITUTUP') {
            abort(422);
        }

        if (
            $sesiPresensi->status_sesi !== 'DRAFT'
            && $data['tanggal'] !== $sesiPresensi->tanggal
        ) {
            abort(422);
        }

        $sesiPresensi->update([
            'id_kelas' => $data['id_kelas'],
            'id_mata_pelajaran' => $data['id_mata_pelajaran'],
            'tanggal' => $data['tanggal'],
            'waktu_mulai' => $data['waktu_mulai'],
            'waktu_selesai' => $data['waktu_selesai'] ?? null,
            'materi' => $data['materi'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Sesi presensi berhasil diubah.',
            'data' => $sesiPresensi->fresh(),
        ], 200);
    }

    public function assignHandler(
        DelegasiGuruPenanganRequest $request,
        int $id_sesi_presensi
    ): JsonResponse {
        $data = $request->validated();

        $sesiPresensi = SesiPresensi::findOrFail(
            $id_sesi_presensi
        );

        $pengguna = $request->user();

        if (! $this->authorization->canAssignHandler(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        if ($sesiPresensi->status_sesi === 'DITUTUP') {
            abort(422);
        }

        if (
            (int) $data['id_guru_penangan'] ===
            (int) $sesiPresensi->id_guru
        ) {
            abort(422);
        }

        $sesiPresensi->update([
            'id_guru_penangan' => $data['id_guru_penangan'],
        ]);

        return response()->json([
            'message' => 'Guru penangan berhasil ditetapkan.',
            'data' => $sesiPresensi->fresh(),
        ], 200);
    }

    public function activate(
        int $id_sesi_presensi
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::findOrFail(
            $id_sesi_presensi
        );

        $pengguna = request()->user();

        if (! $this->authorization->canActivate(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        if ($sesiPresensi->status_sesi !== 'DRAFT') {
            abort(422);
        }

        $sesiPresensi->update([
            'status_sesi' => 'AKTIF',
        ]);

        return response()->json([
            'message' => 'Sesi presensi berhasil diaktifkan.',
            'data' => $sesiPresensi->fresh(),
        ], 200);
    }

    public function close(
        int $id_sesi_presensi
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::findOrFail(
            $id_sesi_presensi
        );

        $pengguna = request()->user();

        if (! $this->authorization->canClose(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        if ($sesiPresensi->status_sesi !== 'AKTIF') {
            abort(422);
        }

        $sesiPresensi->update([
            'status_sesi' => 'DITUTUP',
        ]);

        return response()->json([
            'message' => 'Sesi presensi berhasil ditutup.',
            'data' => $sesiPresensi->fresh(),
        ], 200);
    }
}
