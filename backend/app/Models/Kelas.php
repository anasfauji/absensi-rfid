<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $primaryKey = 'id_kelas';

    protected $fillable = [
        'id_tahun_ajaran',
        'id_jurusan',
        'tingkat',
        'nama_kelas',
        'status',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(
            TahunAjaran::class,
            'id_tahun_ajaran',
            'id_tahun_ajaran'
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

    public function penempatanSiswa(): HasMany
    {
        return $this->hasMany(
            PenempatanSiswa::class,
            'id_kelas',
            'id_kelas'
        );
    }

    public function pengajaran(): HasMany
    {
        return $this->hasMany(
            Pengajaran::class,
            'id_kelas',
            'id_kelas'
        );
    }

    public function waliKelas(): HasMany
    {
        return $this->hasMany(
            WaliKelas::class,
            'id_kelas',
            'id_kelas'
        );
    }
}
