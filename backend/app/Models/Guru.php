<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    protected $table = 'guru';

    protected $primaryKey = 'id_guru';

    protected $fillable = [
        'nip',
        'nama_guru',
        'jenis_kelamin',
        'email',
        'nomor_telepon',
        'foto',
        'status',
    ];

    public function pengguna(): HasOne
    {
        return $this->hasOne(
            Pengguna::class,
            'id_guru',
            'id_guru'
        );
    }

    public function pengajaran(): HasMany
    {
        return $this->hasMany(
            Pengajaran::class,
            'id_guru',
            'id_guru'
        );
    }

    public function waliKelas(): HasMany
    {
        return $this->hasMany(
            WaliKelas::class,
            'id_guru',
            'id_guru'
        );
    }
}
