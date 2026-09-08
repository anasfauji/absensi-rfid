<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pengguna extends Model
{
    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'username',
        'password',
        'nama_tampilan',
        'email',
        'foto',
        'id_guru',
        'id_siswa',
        'status',
        'last_login_at',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(
            Guru::class,
            'id_guru',
            'id_guru'
        );
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function roles(): BelongsToMany
{
    return $this->belongsToMany(
        Role::class,
        'pengguna_role',
        'id_pengguna',
        'id_role'
    );
}
}
