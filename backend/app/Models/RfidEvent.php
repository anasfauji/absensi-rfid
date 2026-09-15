<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidEvent extends Model
{
    protected $table = 'rfid_event';

    protected $primaryKey = 'id_rfid_event';

    protected $fillable = [
        'id_perangkat_rfid',
        'id_kartu_rfid',
        'id_siswa',
        'uid_rfid',
        'waktu_event',
        'waktu_diterima_server',
        'status_proses',
        'hasil_event',
        'keterangan',
    ];

    public function perangkatRfid(): BelongsTo
    {
        return $this->belongsTo(
            PerangkatRfid::class,
            'id_perangkat_rfid',
            'id_perangkat_rfid'
        );
    }

    public function kartuRfid(): BelongsTo
    {
        return $this->belongsTo(
            KartuRfid::class,
            'id_kartu_rfid',
            'id_kartu_rfid'
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
}
