<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'username',
        'password',
        'role',
        'nama_tampilan',
        'email',
        'foto',
        'id_guru',
        'id_siswa',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
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


    public function penugasan(): HasMany
    {
        return $this->hasMany(
            Penugasan::class,
            'id_pengguna',
            'id_pengguna'
        );
    }

    public function presensiGateDibuat(): HasMany
    {
        return $this->hasMany(
            PresensiGate::class,
            'dibuat_oleh',
            'id_pengguna'
        );
    }

    public function presensiGateDiubah(): HasMany
    {
        return $this->hasMany(
            PresensiGate::class,
            'diubah_oleh',
            'id_pengguna'
        );
    }

    public function auditLog(): HasMany
    {
        return $this->hasMany(
            AuditLog::class,
            'id_pengguna',
            'id_pengguna'
        );
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(
            Notifikasi::class,
            'id_pengguna',
            'id_pengguna'
        );
    }
}
