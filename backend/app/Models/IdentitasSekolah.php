<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentitasSekolah extends Model
{
    protected $table = 'identitas_sekolah';

    protected $primaryKey = 'id_identitas_sekolah';

    protected $fillable = [
        'id_sekolah',
        'nama_sekolah',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'telepon',
        'email',
        'website',
        'logo',
        'berlaku_mulai',
        'berlaku_sampai',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(
            Sekolah::class,
            'id_sekolah',
            'id_sekolah'
        );
    }
}
