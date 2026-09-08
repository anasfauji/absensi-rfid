<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sekolah extends Model
{
    protected $table = 'sekolah';

    protected $primaryKey = 'id_sekolah';

    protected $fillable = [
        'kode_sekolah',
        'npsn',
        'status',
    ];

    public function identitasSekolah(): HasMany
    {
        return $this->hasMany(
            IdentitasSekolah::class,
            'id_sekolah',
            'id_sekolah'
        );
    }

    public function tahunAjaran(): HasMany
    {
        return $this->hasMany(
            TahunAjaran::class,
            'id_sekolah',
            'id_sekolah'
        );
    }

    public function jurusan(): HasMany
    {
        return $this->hasMany(
            Jurusan::class,
            'id_sekolah',
            'id_sekolah'
        );
    }
}
