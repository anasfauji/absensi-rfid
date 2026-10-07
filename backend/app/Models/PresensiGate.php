<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiGate extends Model
{
    protected $table = 'presensi_gate';

    protected $primaryKey = 'id_presensi_gate';

    protected $fillable = [
        'id_siswa',
        'tanggal',
        'waktu_masuk',
        'sumber_masuk',
        'keterangan',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(
            Pengguna::class,
            'dibuat_oleh',
            'id_pengguna'
        );
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(
            Pengguna::class,
            'diubah_oleh',
            'id_pengguna'
        );
    }
}
