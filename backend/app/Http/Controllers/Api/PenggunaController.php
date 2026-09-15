<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index(): JsonResponse
    {
        $pengguna = Pengguna::query()
            ->select([
                'id_pengguna',
                'username',
                'role',
                'nama_tampilan',
                'email',
                'status',
            ])
            ->orderBy('id_pengguna')
            ->get();

        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => [
                'pengguna' => $pengguna,
            ],
        ]);
    }

    public function show(int $id_pengguna): JsonResponse
    {
        $pengguna = Pengguna::query()
            ->select([
                'id_pengguna',
                'username',
                'role',
                'nama_tampilan',
                'email',
                'status',
            ])
            ->where('id_pengguna', $id_pengguna)
            ->first();

        if (! $pengguna) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => [
                'pengguna' => $pengguna,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:pengguna,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['ADMIN', 'GURU', 'SISWA'])],
            'nama_tampilan' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:pengguna,email'],
            'status' => ['required', Rule::in(['AKTIF', 'NONAKTIF'])],
        ]);

        $pengguna = Pengguna::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'nama_tampilan' => $validated['nama_tampilan'],
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil dibuat.',
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
        ], 201);
    }

    public function update(Request $request, int $id_pengguna): JsonResponse
    {
        $pengguna = Pengguna::query()
            ->where('id_pengguna', $id_pengguna)
            ->first();

        if (! $pengguna) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pengguna', 'username')->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(['ADMIN', 'GURU', 'SISWA'])],
            'nama_tampilan' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('pengguna', 'email')->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'status' => ['required', Rule::in(['AKTIF', 'NONAKTIF'])],
        ]);

        $pengguna->username = $validated['username'];
        $pengguna->role = $validated['role'];
        $pengguna->nama_tampilan = $validated['nama_tampilan'];
        $pengguna->email = $validated['email'] ?? null;
        $pengguna->status = $validated['status'];

        if (! empty($validated['password'])) {
            $pengguna->password = Hash::make($validated['password']);
        }

        $pengguna->save();

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
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
    }


    public function destroy(Request $request, int $id_pengguna): JsonResponse
    {
        $pengguna = Pengguna::query()
            ->where('id_pengguna', $id_pengguna)
            ->first();

        if (! $pengguna) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        if ($request->user()->id_pengguna === $pengguna->id_pengguna) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        $pengguna->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}
