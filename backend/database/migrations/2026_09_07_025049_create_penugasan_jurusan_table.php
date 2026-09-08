<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_jurusan', function (Blueprint $table) {
            $table->id('id_penugasan_jurusan');

            $table->foreignId('id_penugasan')
                ->constrained('penugasan', 'id_penugasan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_jurusan')
                ->constrained('jurusan', 'id_jurusan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'id_penugasan',
                'id_jurusan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_jurusan');
    }
};