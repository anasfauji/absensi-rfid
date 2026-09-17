<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $pengguna = Pengguna::where('username', $credentials['username'])->first();

        if (!$pengguna || !Hash::check($credentials['password'], $pengguna->password)) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        if ($pengguna->status !== 'AKTIF') {
            return response()->json([
                'message' => 'Akun tidak aktif.',
            ], 403);
        }
        $pengguna->load('roles');
        $token = $pengguna->createToken('api-token')->plainTextToken;


        return response()->json([
            'message' => 'Login berhasil.',
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
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
