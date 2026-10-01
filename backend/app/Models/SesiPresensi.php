<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiPresensi extends Model
{
    protected $table = 'sesi_presensi';

    protected $primaryKey = 'id_sesi_presensi';

    protected $fillable = [
        'id_guru',
        'id_guru_penangan',
        'id_kelas',
        'id_mata_pelajaran',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'status_sesi',
        'materi',
        'keterangan',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(
            Guru::class,
            'id_guru',
            'id_guru'
        );
    }

    public function guruPenangan(): BelongsTo
    {
        return $this->belongsTo(
            Guru::class,
            'id_guru_penangan',
            'id_guru'
        );
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(
            Kelas::class,
            'id_kelas',
            'id_kelas'
        );
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(
            MataPelajaran::class,
            'id_mata_pelajaran',
            'id_mata_pelajaran'
        );
    }

    public function presensiKelas(): HasMany
    {
        return $this->hasMany(
            PresensiKelas::class,
            'id_sesi_presensi',
            'id_sesi_presensi'
        );
    }
}
