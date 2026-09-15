<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiKelas extends Model
{
    protected $table = 'presensi_kelas';

    protected $primaryKey = 'id_presensi_kelas';

    protected $fillable = [
        'id_sesi_presensi',
        'id_siswa',
        'status',
        'waktu_presensi',
        'sumber',
        'keterangan',
    ];

    public function sesiPresensi(): BelongsTo
    {
        return $this->belongsTo(
            SesiPresensi::class,
            'id_sesi_presensi',
            'id_sesi_presensi'
        );
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }
}
