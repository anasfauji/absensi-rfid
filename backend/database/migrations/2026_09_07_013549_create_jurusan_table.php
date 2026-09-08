<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusan', function (Blueprint $table) {
            $table->id('id_jurusan');

            $table->foreignId('id_sekolah')
                ->constrained('sekolah', 'id_sekolah')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('kode_jurusan', 20);
            $table->string('nama_jurusan', 150);

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF',
            ])->default('AKTIF');

            $table->timestamps();

            $table->unique([
                'id_sekolah',
                'kode_jurusan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan');
    }
};