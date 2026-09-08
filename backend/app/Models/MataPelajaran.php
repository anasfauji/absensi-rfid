<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    protected $primaryKey = 'id_mata_pelajaran';

    protected $fillable = [
        'kode_mata_pelajaran',
        'nama_mata_pelajaran',
        'kelompok',
        'status',
        'keterangan',
    ];

    public function pengajaran(): HasMany
    {
        return $this->hasMany(
            Pengajaran::class,
            'id_mata_pelajaran',
            'id_mata_pelajaran'
        );
    }
}
