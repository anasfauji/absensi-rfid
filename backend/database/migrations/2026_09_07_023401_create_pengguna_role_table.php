<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna_role', function (Blueprint $table) {
            $table->id('id_pengguna_role');

            $table->foreignId('id_pengguna')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_role')
                ->constrained('role', 'id_role')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'id_pengguna',
                'id_role',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna_role');
    }
};