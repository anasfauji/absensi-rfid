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
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id('id_kegiatan');

            $table->foreignId('id_tahun_ajaran')
                ->constrained('tahun_ajaran', 'id_tahun_ajaran')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nama_kegiatan', 150);

            $table->enum('jenis_kegiatan', [
                'PKL',
                'UJIAN',
                'CLASSMEETING',
                'KEGIATAN_LAIN'
            ]);

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai');

            $table->enum('status', [
                'RENCANA',
                'AKTIF',
                'SELESAI',
                'DIBATALKAN'
            ])->default('RENCANA');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->index(['id_tahun_ajaran', 'status']);
            $table->index(['jenis_kegiatan', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
