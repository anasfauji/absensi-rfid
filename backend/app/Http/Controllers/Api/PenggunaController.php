<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index(): JsonResponse
    {
        $pengguna = Pengguna::with('roles')
            ->select([
                'id_pengguna',
                'username',
                'nama_tampilan',
                'email',
                'status',
            ])
            ->orderBy('id_pengguna')
            ->get()
            ->map(function ($item) {
                return [
                    'id_pengguna' => $item->id_pengguna,
                    'username' => $item->username,
                    'roles' => $item->roles
                        ->pluck('kode_role')
                        ->values()
                        ->all(),
                    'nama_tampilan' => $item->nama_tampilan,
                    'email' => $item->email,
                    'status' => $item->status,
                ];
            });

        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => [
                'pengguna' => $pengguna,
            ],
        ]);
    }

    public function show(int $id_pengguna): JsonResponse
    {
        $pengguna = Pengguna::with('roles')
            ->select([
                'id_pengguna',
                'username',
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
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                'unique:pengguna,username',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                'exists:role,kode_role',
            ],
            'id_siswa' => [
                'nullable',
                'integer',
                'exists:siswa,id_siswa',
            ],
            'nama_tampilan' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:pengguna,email',
            ],
            'status' => [
                'required',
                Rule::in(['AKTIF', 'NONAKTIF']),
            ],
        ]);

        $pengguna = DB::transaction(function () use ($validated) {

            $pengguna = Pengguna::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'nama_tampilan' => $validated['nama_tampilan'],
                'email' => $validated['email'] ?? null,
                'id_siswa' => $validated['id_siswa'] ?? null,
                'status' => $validated['status'],
            ]);

            $roleIds = Role::whereIn('kode_role', $validated['roles'])
                ->pluck('id_role')
                ->all();

            $pengguna->roles()->sync($roleIds);

            return $pengguna->load('roles');
        });

        return response()->json([
            'message' => 'Pengguna berhasil dibuat.',
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
                Rule::unique('pengguna', 'username')
                    ->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                'exists:role,kode_role',
            ],
            'id_siswa' => [
                'nullable',
                'integer',
                'exists:siswa,id_siswa',
            ],
            'nama_tampilan' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('pengguna', 'email')
                    ->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'status' => [
                'required',
                Rule::in(['AKTIF', 'NONAKTIF']),
            ],
        ]);

        $pengguna = DB::transaction(function () use ($pengguna, $validated) {

            $pengguna->username = $validated['username'];
            $pengguna->nama_tampilan = $validated['nama_tampilan'];
            $pengguna->email = $validated['email'] ?? null;
            $pengguna->id_siswa = $validated['id_siswa'] ?? null;
            $pengguna->status = $validated['status'];

            if (! empty($validated['password'])) {
                $pengguna->password = Hash::make($validated['password']);
            }

            $pengguna->save();

            $roleIds = Role::whereIn('kode_role', $validated['roles'])
                ->pluck('id_role')
                ->all();

            $pengguna->roles()->sync($roleIds);

            return $pengguna->load('roles');
        });

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
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
