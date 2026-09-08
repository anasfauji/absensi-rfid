<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_kelas', function (Blueprint $table) {
            $table->id('id_penugasan_kelas');

            $table->foreignId('id_penugasan')
                ->constrained('penugasan', 'id_penugasan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_kelas')
                ->constrained('kelas', 'id_kelas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'id_penugasan',
                'id_kelas',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_kelas');
    }
};