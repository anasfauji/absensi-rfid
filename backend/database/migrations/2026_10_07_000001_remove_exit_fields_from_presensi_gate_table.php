<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi_gate', function (Blueprint $table) {
            $table->dropColumn([
                'waktu_keluar',
                'sumber_keluar',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('presensi_gate', function (Blueprint $table) {
            $table->dateTime('waktu_keluar')
                ->nullable()
                ->after('waktu_masuk');

            $table->enum('sumber_keluar', [
                'RFID',
                'MANUAL',
                'SYSTEM',
            ])
                ->nullable()
                ->after('sumber_masuk');
        });
    }
};
