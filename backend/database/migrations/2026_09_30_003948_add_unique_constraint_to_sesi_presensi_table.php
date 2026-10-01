
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->unique(
                [
                    'id_guru',
                    'id_kelas',
                    'id_mata_pelajaran',
                    'tanggal',
                ],
                'sesi_presensi_guru_kelas_mapel_tanggal_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->dropUnique(
                'sesi_presensi_guru_kelas_mapel_tanggal_unique'
            );
        });
    }
};
