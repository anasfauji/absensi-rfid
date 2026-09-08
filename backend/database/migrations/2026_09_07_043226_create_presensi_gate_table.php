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
        Schema::create('presensi_gate', function (Blueprint $table) {
            $table->id('id_presensi_gate');

            $table->foreignId('id_siswa')
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('tanggal');

            $table->dateTime('waktu_masuk')
                ->nullable();

            $table->dateTime('waktu_keluar')
                ->nullable();

            $table->enum('status', [
                'HADIR',
                'TERLAMBAT',
                'SAKIT',
                'IZIN',
                'ALPA',
                'DISPENSASI'
            ])->default('HADIR');

            $table->enum('sumber_masuk', [
                'RFID',
                'MANUAL',
                'SYSTEM'
            ])->default('SYSTEM');

            $table->enum('sumber_keluar', [
                'RFID',
                'MANUAL',
                'SYSTEM'
            ])->nullable();

            $table->text('keterangan')
                ->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('diubah_oleh')
                ->nullable()
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'id_siswa',
                'tanggal',
            ]);

            $table->index(['tanggal', 'status']);
            $table->index(['id_siswa', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_gate');
    }
};
