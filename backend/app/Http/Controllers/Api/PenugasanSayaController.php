<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PenugasanSayaRequest;
use Illuminate\Http\JsonResponse;

class PenugasanSayaController extends Controller
{
    public function index(PenugasanSayaRequest $request): JsonResponse
    {
        $pengguna = $request->user();

        if (! $pengguna->roles()
            ->where('kode_role', 'WALI_KELAS')
            ->exists()) {
            abort(403);
        }

        $tanggal = $request->validated('tanggal');

        $penugasan = $pengguna->penugasan()
            ->whereHas('role', function ($query) {
                $query->where('kode_role', 'WALI_KELAS');
            })
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($query) use ($tanggal) {
                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->with([
                'penugasanKelas' => function ($query) {
                    $query->select([
                        'id_penugasan_kelas',
                        'id_penugasan',
                        'id_kelas',
                    ]);
                },
                'penugasanKelas.kelas:id_kelas,nama_kelas',
            ])
            ->orderBy('tanggal_mulai')
            ->orderBy('id_penugasan')
            ->get([
                'id_penugasan',
                'tanggal_mulai',
                'tanggal_selesai',
                'status',
            ])
            ->map(function ($item) {
                return [
                    'id_penugasan' => $item->id_penugasan,
                    'tanggal_mulai' => $item->tanggal_mulai,
                    'tanggal_selesai' => $item->tanggal_selesai,
                    'status' => $item->status,
                    'kelas' => $item->penugasanKelas
                        ->map(function ($penugasanKelas) {
                            return [
                                'id_kelas' => $penugasanKelas->kelas->id_kelas,
                                'nama_kelas' => $penugasanKelas->kelas->nama_kelas,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'penugasan' => $penugasan,
            ],
        ], 200);
    }
}
