<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id('id_penugasan');

            $table->foreignId('id_pengguna')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_role')
                ->constrained('role', 'id_role')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();

            $table->enum('status', [
                'AKTIF',
                'SELESAI',
                'DIBATALKAN',
            ])->default('AKTIF');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->index([
                'id_pengguna',
                'status',
            ]);

            $table->index([
                'id_role',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan');
    }
};