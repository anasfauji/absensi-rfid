<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $primaryKey = 'id_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'aksi',
        'nama_tabel',
        'id_data',
        'data_sebelum',
        'data_sesudah',
        'alasan',
        'alamat_ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'data_sebelum' => 'array',
        'data_sesudah' => 'array',
        'created_at' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna',
            'id_pengguna'
        );
    }
}
