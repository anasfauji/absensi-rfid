<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi_gate', function (Blueprint $table) {
            $table->dropIndex(['tanggal', 'status']);
            $table->dropIndex(['id_siswa', 'status']);
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('presensi_gate', function (Blueprint $table) {
            $table->enum('status', [
                'HADIR',
                'TERLAMBAT',
                'SAKIT',
                'IZIN',
                'ALPA',
                'DISPENSASI',
            ])->nullable()->after('waktu_keluar');

            $table->index(['tanggal', 'status']);
            $table->index(['id_siswa', 'status']);
        });
    }
};
