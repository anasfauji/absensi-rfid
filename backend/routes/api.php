<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PenggunaController;
use App\Http\Controllers\Api\PresensiKelasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EvaluasiPresensiController;
use App\Http\Controllers\Api\SesiPresensiController;
use App\Http\Controllers\Api\SesiPresensiQueryController;
use App\Http\Controllers\Api\RfidGateController;
use App\Http\Controllers\Api\PresensiGateController;
use App\Http\Controllers\Api\PenugasanSayaController;
use App\Http\Controllers\Api\RfidEventController;


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
    Route::get('/penugasan-saya', [PenugasanSayaController::class, 'index']);

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

Route::middleware([
    'auth:sanctum',
    'permission:presensi_kelas.koreksi',
])->put(
    '/sesi-presensi/{id_sesi_presensi}/presensi/{id_siswa}',
    [PresensiKelasController::class, 'update']
);

Route::middleware('auth:sanctum')->put(
    '/sesi-presensi/{id_sesi_presensi}/aktifkan',
    [SesiPresensiController::class, 'activate']
);

Route::middleware('auth:sanctum')->put(
    '/sesi-presensi/{id_sesi_presensi}/tutup',
    [SesiPresensiController::class, 'close']
);

Route::middleware('auth:sanctum')->get(
    '/sesi-presensi',
    [SesiPresensiQueryController::class, 'index']
);

Route::middleware([
    'auth:sanctum',
    'permission:presensi_kelas.lihat',
])->get(
    '/sesi-presensi/{id_sesi_presensi}/presensi',
    [SesiPresensiQueryController::class, 'showPresensi']
);

Route::middleware('auth:sanctum')->get(
    '/sesi-presensi/{id_sesi_presensi}',
    [SesiPresensiQueryController::class, 'show']
);

Route::middleware([
    'auth:sanctum',
    'permission:presensi_gate.koreksi',
    'role:ADMIN,OPERATOR',
])->put(
    '/presensi-gate/{id_presensi_gate}',
    [PresensiGateController::class, 'update']
);

Route::middleware([
    'auth:sanctum',
    'permission:presensi_gate.lihat',
    'role:ADMIN,OPERATOR',
])->get(
    '/presensi-gate',
    [PresensiGateController::class, 'index']
);

Route::middleware([
    'auth:sanctum',
    'permission:presensi_gate.lihat',
    'role:ADMIN,OPERATOR',
])->get(
    '/presensi-gate/{id_presensi_gate}',
    [PresensiGateController::class, 'show']
);

Route::middleware([
    'auth:sanctum',
    'permission:rfid_event.lihat',
    'role:ADMIN,OPERATOR',
])->get(
    '/rfid-event',
    [RfidEventController::class, 'index']
);

Route::middleware([
    'auth:sanctum',
    'permission:rfid_event.lihat',
    'role:ADMIN,OPERATOR',
])->get(
    '/rfid-event/{id_rfid_event}',
    [RfidEventController::class, 'show']
);

Route::post('/rfid/tap', [RfidGateController::class, 'tap']);
