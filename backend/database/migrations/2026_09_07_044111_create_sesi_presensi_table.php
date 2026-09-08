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
        Schema::create('sesi_presensi', function (Blueprint $table) {
            $table->id('id_sesi_presensi');

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

            $table->date('tanggal');

            $table->time('waktu_mulai');

            $table->time('waktu_selesai')
                ->nullable();

            $table->enum('status_sesi', [
                'DRAFT',
                'AKTIF',
                'DITUTUP'
            ])->default('DRAFT');

            $table->string('materi', 255)
                ->nullable();

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->index(['id_guru', 'tanggal']);
            $table->index(['id_kelas', 'tanggal']);
            $table->index(['id_mata_pelajaran', 'tanggal']);
            $table->index(['tanggal', 'status_sesi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesi_presensi');
    }
};
