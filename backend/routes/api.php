<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PenggunaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Temporary route untuk pengujian auth:sanctum.
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $pengguna = $request->user();

    return response()->json([
        'message' => 'Token valid.',
        'data' => [
            'pengguna' => [
                'id_pengguna' => $pengguna->id_pengguna,
                'username' => $pengguna->username,
                'role' => $pengguna->role,
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



