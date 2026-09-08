<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penempatan_siswa', function (Blueprint $table) {
            $table->id('id_penempatan');

            $table->foreignId('id_siswa')
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_kelas')
                ->constrained('kelas', 'id_kelas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();

            $table->enum('status', [
                'AKTIF',
                'SELESAI',
                'DIBATALKAN',
            ])->default('AKTIF');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->index([
                'id_siswa',
                'status',
            ]);

            $table->index([
                'id_kelas',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penempatan_siswa');
    }
};