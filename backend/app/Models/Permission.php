<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsToMany;

class Permission extends Model
{
    protected $table = 'permission';

    protected $primaryKey = 'id_permission';

    protected $fillable = [
        'kode_permission',
        'nama_permission',
        'modul',
        'aksi',
        'deskripsi',
        'status',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permission',
            'id_permission',
            'id_role'
        );
    }
}
