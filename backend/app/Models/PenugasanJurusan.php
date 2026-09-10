<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenugasanJurusan extends Model
{
    protected $table = 'penugasan_jurusan';

    protected $primaryKey = 'id_penugasan_jurusan';

    protected $fillable = [
        'id_penugasan',
        'id_jurusan',
    ];

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(
            Penugasan::class,
            'id_penugasan',
            'id_penugasan'
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
