<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');

            $table->foreignId('id_tahun_ajaran')
                ->constrained('tahun_ajaran', 'id_tahun_ajaran')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_jurusan')
                ->constrained('jurusan', 'id_jurusan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('tingkat');

            $table->string('nama_kelas', 50);

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF',
            ])->default('AKTIF');

            $table->timestamps();

            $table->unique([
                'id_tahun_ajaran',
                'id_jurusan',
                'nama_kelas',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};