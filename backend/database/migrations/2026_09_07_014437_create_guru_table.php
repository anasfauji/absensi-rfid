<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');

            $table->string('nip', 30)
                ->nullable()
                ->unique();

            $table->string('nama_guru', 150);

            $table->enum('jenis_kelamin', [
                'L',
                'P',
            ]);

            $table->string('email', 150)
                ->nullable();

            $table->string('nomor_telepon', 30)
                ->nullable();

            $table->string('foto', 255)
                ->nullable();

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF',
            ])->default('AKTIF');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru');
    }
};