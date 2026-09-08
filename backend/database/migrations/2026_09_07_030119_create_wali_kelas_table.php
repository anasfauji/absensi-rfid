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
        Schema::create('wali_kelas', function (Blueprint $table) {
            $table->id('id_wali_kelas');

            $table->foreignId('id_guru')
                ->constrained('guru', 'id_guru')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_kelas')
                ->constrained('kelas', 'id_kelas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai')
                ->nullable();

            $table->enum('status', [
                'AKTIF',
                'SELESAI',
                'DIBATALKAN'
            ])->default('AKTIF');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->index(['id_guru', 'status']);
            $table->index(['id_kelas', 'status']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_kelas');
    }
};
