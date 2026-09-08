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
        Schema::create('pengecualian_kewajiban', function (Blueprint $table) {
            $table->id('id_pengecualian');

            $table->foreignId('id_siswa')
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('jenis', [
                'DISPENSASI',
                'LAINNYA'
            ]);

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai');

            $table->text('alasan');

            $table->enum('status', [
                'AKTIF',
                'SELESAI',
                'DIBATALKAN'
            ])->default('AKTIF');

            $table->timestamps();

            $table->index(['id_siswa', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengecualian_kewajiban');
    }
};
