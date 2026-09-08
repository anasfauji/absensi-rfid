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
        Schema::create('presensi_kelas', function (Blueprint $table) {
            $table->id('id_presensi_kelas');

            $table->foreignId('id_sesi_presensi')
                ->constrained('sesi_presensi', 'id_sesi_presensi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_siswa')
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', [
                'HADIR',
                'TERLAMBAT',
                'SAKIT',
                'IZIN',
                'DISPENSASI',
                'ALPA'
            ])->default('HADIR');

            $table->dateTime('waktu_presensi')
                ->nullable();

            $table->enum('sumber', [
                'SISTEM',
                'GURU',
                'WALI_KELAS'
            ])->default('SISTEM');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'id_sesi_presensi',
                'id_siswa',
            ]);

            $table->index(['id_siswa', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_kelas');
    }
};
