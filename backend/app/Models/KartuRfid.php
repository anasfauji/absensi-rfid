<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuRfid extends Model
{
    protected $table = 'kartu_rfid';

    protected $primaryKey = 'id_kartu_rfid';

    protected $fillable = [
        'id_siswa',
        'uid_rfid',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function rfidEvent(): HasMany
    {
        return $this->hasMany(
            RfidEvent::class,
            'id_kartu_rfid',
            'id_kartu_rfid'
        );
    }
}
