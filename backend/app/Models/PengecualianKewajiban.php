<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengecualianKewajiban extends Model
{
    protected $table = 'pengecualian_kewajiban';

    protected $primaryKey = 'id_pengecualian';

    protected $fillable = [
        'id_siswa',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'status',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }
}
