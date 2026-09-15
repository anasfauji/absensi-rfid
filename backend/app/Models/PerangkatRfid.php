<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerangkatRfid extends Model
{
    protected $table = 'perangkat_rfid';

    protected $primaryKey = 'id_perangkat_rfid';

    protected $fillable = [
        'kode_perangkat',
        'nama_perangkat',
        'lokasi',
        'jenis_perangkat',
        'status',
        'terakhir_terhubung_at',
    ];
    
    public function rfidEvent(): HasMany
    {
        return $this->hasMany(
            RfidEvent::class,
            'id_perangkat_rfid',
            'id_perangkat_rfid'
        );
    }
}
