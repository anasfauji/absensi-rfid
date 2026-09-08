<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id('id_tahun_ajaran');

            $table->foreignId('id_sekolah')
                ->constrained('sekolah', 'id_sekolah')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nama_tahun_ajaran', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->enum('semester_aktif', [
                'GANJIL',
                'GENAP',
            ]);

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF',
            ])->default('NONAKTIF');

            $table->timestamps();

            $table->index([
                'id_sekolah',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajaran');
    }
};