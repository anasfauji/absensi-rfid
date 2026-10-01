<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluasiPresensiRequest;
use App\Http\Resources\AttendanceEvaluationResource;
use App\Services\AttendanceEvaluationService;
use App\Services\AttendanceScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EvaluasiPresensiController extends Controller
{
    public function __construct(
        private AttendanceScope $attendanceScope,
        private AttendanceEvaluationService $attendanceEvaluationService
    ) {}

    public function show(
        EvaluasiPresensiRequest $request
    ): JsonResponse {
        $pengguna = $request->user();

        $idSiswa = (int) $request->validated('id_siswa');

        $tanggal = Carbon::parse(
            $request->validated('tanggal')
        );

        $bolehMelihat = $this->attendanceScope
            ->canViewStudentEvaluation(
                $pengguna,
                $idSiswa,
                $tanggal
            );

        if (! $bolehMelihat) {
            abort(403);
        }

        $hasilEvaluasi = $this->attendanceEvaluationService
            ->evaluate(
                $idSiswa,
                $tanggal
            );

        return (new AttendanceEvaluationResource(
            $hasilEvaluasi
        ))->response();
    }
}
