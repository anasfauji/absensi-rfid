<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'id_role';

    protected $fillable = [
        'kode_role',
        'nama_role',
        'deskripsi',
        'status',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'id_role',
            'id_permission'
        );
    }

    public function pengguna(): BelongsToMany
    {
        return $this->belongsToMany(
            Pengguna::class,
            'pengguna_role',
            'id_role',
            'id_pengguna'
        );
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(
            Penugasan::class,
            'id_role',
            'id_role'
        );
    }
}
