<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $primaryKey = 'id_tahun_ajaran';

    protected $fillable = [
        'id_sekolah',
        'nama_tahun_ajaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'semester_aktif',
        'status',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(
            Sekolah::class,
            'id_sekolah',
            'id_sekolah'
        );
    }

    public function kalender(): HasMany
    {
        return $this->hasMany(
            Kalender::class,
            'id_tahun_ajaran',
            'id_tahun_ajaran'
        );
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(
            Kelas::class,
            'id_tahun_ajaran',
            'id_tahun_ajaran'
        );
    }
}