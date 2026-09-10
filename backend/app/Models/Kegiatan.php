<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $primaryKey = 'id_kegiatan';

    protected $fillable = [
        'id_tahun_ajaran',
        'nama_kegiatan',
        'jenis_kegiatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
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

    public function kegiatanJurusan(): HasMany
    {
        return $this->hasMany(
            KegiatanJurusan::class,
            'id_kegiatan',
            'id_kegiatan'
        );
    }

    public function kegiatanKelas(): HasMany
    {
        return $this->hasMany(
            KegiatanKelas::class,
            'id_kegiatan',
            'id_kegiatan'
        );
    }

    public function kegiatanSiswa(): HasMany
    {
        return $this->hasMany(
            KegiatanSiswa::class,
            'id_kegiatan',
            'id_kegiatan'
        );
    }
}
