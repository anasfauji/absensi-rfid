<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SesiPresensi;
use App\Models\PenempatanSiswa;
use App\Services\SesiPresensiAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesiPresensiQueryController extends Controller
{
    public function __construct(
        private SesiPresensiAuthorization $authorization
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        $query = SesiPresensi::query()
            ->with([
                'guru',
                'guruPenangan',
                'kelas',
                'mataPelajaran',
            ]);

        $isAdminOrOperator = $pengguna
            ->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists();

        if ($isAdminOrOperator) {
            $sesiPresensi = $query
                ->orderByDesc('tanggal')
                ->orderByDesc('waktu_mulai')
                ->get();
        } else {
            $isWaliKelas = $pengguna
                ->roles()
                ->where('kode_role', 'WALI_KELAS')
                ->exists();

            $isGuru = $pengguna
                ->roles()
                ->where('kode_role', 'GURU')
                ->exists();

            if (
                ! $isWaliKelas
                && (! $isGuru || $pengguna->id_guru === null)
            ) {
                abort(403);
            }

            $sesiPresensi = $query
                ->where(function ($query) use (
                    $pengguna,
                    $isWaliKelas,
                    $isGuru
                ) {
                    if ($isWaliKelas) {
                        $query->whereExists(function ($assignmentQuery) use ($pengguna) {
                            $assignmentQuery
                                ->selectRaw('1')
                                ->from('penugasan as wali_assignment')
                                ->join(
                                    'role as wali_role',
                                    'wali_role.id_role',
                                    '=',
                                    'wali_assignment.id_role'
                                )
                                ->join(
                                    'penugasan_kelas as wali_assignment_class',
                                    'wali_assignment_class.id_penugasan',
                                    '=',
                                    'wali_assignment.id_penugasan'
                                )
                                ->where(
                                    'wali_assignment.id_pengguna',
                                    $pengguna->id_pengguna
                                )
                                ->where('wali_role.kode_role', 'WALI_KELAS')
                                ->where('wali_assignment.status', 'AKTIF')
                                ->whereColumn(
                                    'wali_assignment.tanggal_mulai',
                                    '<=',
                                    'sesi_presensi.tanggal'
                                )
                                ->where(function ($dateQuery) {
                                    $dateQuery
                                        ->whereNull('wali_assignment.tanggal_selesai')
                                        ->orWhereColumn(
                                            'wali_assignment.tanggal_selesai',
                                            '>=',
                                            'sesi_presensi.tanggal'
                                        );
                                })
                                ->whereColumn(
                                    'wali_assignment_class.id_kelas',
                                    'sesi_presensi.id_kelas'
                                );
                        });
                    }

                    if ($isGuru && $pengguna->id_guru !== null) {
                        $guruScope = function ($guruQuery) use ($pengguna) {
                            $guruQuery
                                ->where('id_guru', $pengguna->id_guru)
                                ->orWhere(
                                    'id_guru_penangan',
                                    $pengguna->id_guru
                                );
                        };

                        if ($isWaliKelas) {
                            $query->orWhere($guruScope);
                        } else {
                            $query->where($guruScope);
                        }
                    }
                })
                ->orderByDesc('tanggal')
                ->orderByDesc('waktu_mulai')
                ->get();
        }

        return response()->json([
            'message' => 'Daftar sesi presensi berhasil diambil.',
            'data' => [
                'sesi_presensi' => $sesiPresensi,
            ],
        ], 200);
    }

    public function show(
        Request $request,
        int $id_sesi_presensi
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::with([
            'guru',
            'guruPenangan',
            'kelas',
            'mataPelajaran',
        ])->findOrFail($id_sesi_presensi);

        $pengguna = $request->user();

        if (! $this->authorization->canViewSession(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        return response()->json([
            'message' => 'Detail sesi presensi berhasil diambil.',
            'data' => [
                'sesi_presensi' => $sesiPresensi,
            ],
        ], 200);
    }

    public function showPresensi(
        Request $request,
        int $id_sesi_presensi
    ): JsonResponse {
        $sesiPresensi = SesiPresensi::with([
            'guru',
            'guruPenangan',
            'kelas',
            'mataPelajaran',
            'presensiKelas',
        ])->findOrFail($id_sesi_presensi);

        $pengguna = $request->user();

        if (! $this->authorization->canViewSession(
            $pengguna,
            $sesiPresensi
        )) {
            abort(403);
        }

        $tanggal = $sesiPresensi->tanggal;

        $penempatanSiswa = PenempatanSiswa::query()
            ->with('siswa')
            ->where('id_kelas', $sesiPresensi->id_kelas)
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($query) use ($tanggal) {
                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate(
                        'tanggal_selesai',
                        '>=',
                        $tanggal
                    );
            })
            ->get();

        $presensiBySiswa = $sesiPresensi
            ->presensiKelas
            ->keyBy('id_siswa');

        $presensi = $penempatanSiswa->map(
            function (PenempatanSiswa $penempatan) use ($presensiBySiswa) {
                $siswa = $penempatan->siswa;
                $presensiKelas = $presensiBySiswa->get(
                    $siswa->id_siswa
                );

                return [
                    'id_siswa' => $siswa->id_siswa,
                    'nis' => $siswa->nis,
                    'nama_siswa' => $siswa->nama_siswa,
                    'status' => $presensiKelas?->status,
                    'waktu_presensi' => $presensiKelas?->waktu_presensi,
                    'sumber' => $presensiKelas?->sumber,
                    'keterangan' => $presensiKelas?->keterangan,
                ];
            }
        )->values();

        return response()->json([
            'message' => 'Daftar presensi sesi berhasil diambil.',
            'data' => [
                'sesi_presensi' => $sesiPresensi,
                'presensi' => $presensi,
            ],
        ], 200);
    }
}
