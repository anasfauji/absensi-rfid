<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanSiswa extends Model
{
    protected $table = 'kegiatan_siswa';

    protected $primaryKey = 'id_kegiatan_siswa';

    protected $fillable = [
        'id_kegiatan',
        'id_siswa',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(
            Kegiatan::class,
            'id_kegiatan',
            'id_kegiatan'
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
