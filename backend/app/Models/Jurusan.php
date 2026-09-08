<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    protected $table = 'jurusan';

    protected $primaryKey = 'id_jurusan';

    protected $fillable = [
        'id_sekolah',
        'kode_jurusan',
        'nama_jurusan',
        'status',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(
            Sekolah::class,
            'id_sekolah',
            'id_sekolah'
        );
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(
            Kelas::class,
            'id_jurusan',
            'id_jurusan'
        );
    }
}
