<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Kalender;

class Kalender extends Model
{
    protected $table = 'kalender';

    protected $primaryKey = 'id_kalender';

    protected $fillable = [
        'id_tahun_ajaran',
        'tanggal',
        'status_hari',
        'keterangan',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(
            TahunAjaran::class,
            'id_tahun_ajaran',
            'id_tahun_ajaran'
        );
    }
}
