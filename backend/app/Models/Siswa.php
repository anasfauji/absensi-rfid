<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'nama_siswa',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'nomor_telepon',
        'alamat',
        'foto',
        'status',
    ];

    public function pengguna(): HasOne
    {
        return $this->hasOne(
            Pengguna::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function penempatanSiswa(): HasMany
    {
        return $this->hasMany(
            PenempatanSiswa::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function pengecualianKewajiban(): HasMany
    {
        return $this->hasMany(
            PengecualianKewajiban::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function kartuRfid(): HasMany
    {
        return $this->hasMany(
            KartuRfid::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function rfidEvent(): HasMany
    {
        return $this->hasMany(
            RfidEvent::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function presensiGate(): HasMany
    {
        return $this->hasMany(
            PresensiGate::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function presensiKelas(): HasMany
    {
        return $this->hasMany(
            PresensiKelas::class,
            'id_siswa',
            'id_siswa'
        );
    }
}
