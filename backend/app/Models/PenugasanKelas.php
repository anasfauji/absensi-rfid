<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenugasanKelas extends Model
{
    protected $table = 'penugasan_kelas';

    protected $primaryKey = 'id_penugasan_kelas';

    protected $fillable = [
        'id_penugasan',
        'id_kelas',
    ];

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(
            Penugasan::class,
            'id_penugasan',
            'id_penugasan'
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
