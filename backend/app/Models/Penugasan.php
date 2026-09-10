<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    protected $primaryKey = 'id_penugasan';

    protected $fillable = [
        'id_pengguna',
        'id_role',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'keterangan',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna',
            'id_pengguna'
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'id_role',
            'id_role'
        );
    }

    public function penugasanKelas(): HasMany
    {
        return $this->hasMany(
            PenugasanKelas::class,
            'id_penugasan',
            'id_penugasan'
        );
    }

    public function penugasanJurusan(): HasMany
    {
        return $this->hasMany(
            PenugasanJurusan::class,
            'id_penugasan',
            'id_penugasan'
        );
    }
}
