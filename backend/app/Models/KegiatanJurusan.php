<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanJurusan extends Model
{
    protected $table = 'kegiatan_jurusan';

    protected $primaryKey = 'id_kegiatan_jurusan';

    protected $fillable = [
        'id_kegiatan',
        'id_jurusan',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(
            Kegiatan::class,
            'id_kegiatan',
            'id_kegiatan'
        );
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(
            Jurusan::class,
            'id_jurusan',
            'id_jurusan'
        );
    }
}
