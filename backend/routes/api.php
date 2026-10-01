<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PenggunaController;
use App\Http\Controllers\Api\PresensiKelasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EvaluasiPresensiController;
use App\Http\Controllers\Api\SesiPresensiController;


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Temporary route untuk pengujian auth:sanctum.
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $pengguna = $request->user()->load('roles');

    return response()->json([
        'message' => 'Token valid.',
        'data' => [
            'pengguna' => [
                'id_pengguna' => $pengguna->id_pengguna,
                'username' => $pengguna->username,
                'roles' => $pengguna->roles
                    ->pluck('kode_role')
                    ->values()
                    ->all(),
                'nama_tampilan' => $pengguna->nama_tampilan,
                'email' => $pengguna->email,
                'status' => $pengguna->status,
            ],
        ],
    ]);
});

Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
    Route::get('/pengguna', [PenggunaController::class, 'index']);
    Route::post('/pengguna', [PenggunaController::class, 'store']);
    Route::get('/pengguna/{id_pengguna}', [PenggunaController::class, 'show']);
    Route::put('/pengguna/{id_pengguna}', [PenggunaController::class, 'update']);
    Route::delete('/pengguna/{id_pengguna}', [PenggunaController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get(
        '/siswa/{id_siswa}/evaluasi-presensi',
        [EvaluasiPresensiController::class, 'show']
    );
});

Route::middleware('auth:sanctum')->post(
    '/sesi-presensi',
    [SesiPresensiController::class, 'store']
);

Route::middleware('auth:sanctum')->put(
    '/sesi-presensi/{id_sesi_presensi}',
    [SesiPresensiController::class, 'update']
);

Route::middleware('auth:sanctum')->put(
    '/sesi-presensi/{id_sesi_presensi}/penangan',
    [SesiPresensiController::class, 'assignHandler']
);

Route::middleware('auth:sanctum')->put(
    '/sesi-presensi/{id_sesi_presensi}/presensi/{id_siswa}',
    [PresensiKelasController::class, 'update']
);