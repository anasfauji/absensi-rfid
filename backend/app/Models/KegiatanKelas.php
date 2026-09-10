<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanKelas extends Model
{
    protected $table = 'kegiatan_kelas';

    protected $primaryKey = 'id_kegiatan_kelas';

    protected $fillable = [
        'id_kegiatan',
        'id_kelas',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(
            Kegiatan::class,
            'id_kegiatan',
            'id_kegiatan'
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
}
