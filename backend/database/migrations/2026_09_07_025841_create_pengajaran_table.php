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
        Schema::create('pengajaran', function (Blueprint $table) {
            $table->id('id_pengajaran');

            $table->foreignId('id_guru')
                ->constrained('guru', 'id_guru')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_kelas')
                ->constrained('kelas', 'id_kelas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_mata_pelajaran')
                ->constrained('mata_pelajaran', 'id_mata_pelajaran')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('jumlah_jp');

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
            $table->index(['id_mata_pelajaran', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajaran');
    }
};
