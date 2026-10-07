<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Pengguna;
use Illuminate\Http\Request;

class AuditLogService
{
    public function __construct(
        private Request $request
    ) {}

    public function catat(
        Pengguna $pengguna,
        string $aksi,
        string $namaTabel,
        string|int $idData,
        ?array $dataSebelum = null,
        ?array $dataSesudah = null,
        ?string $alasan = null
    ): AuditLog {
        return AuditLog::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'aksi' => $aksi,
            'nama_tabel' => $namaTabel,
            'id_data' => (string) $idData,
            'data_sebelum' => $dataSebelum,
            'data_sesudah' => $dataSesudah,
            'alasan' => $alasan,
            'alamat_ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
