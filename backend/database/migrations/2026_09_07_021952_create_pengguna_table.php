<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');

            $table->string('username', 100)
                ->unique();

            $table->string('password', 255);

            $table->string('nama_tampilan', 150);

            $table->string('email', 150)
                ->nullable()
                ->unique();

            $table->string('foto', 255)
                ->nullable();

            $table->foreignId('id_guru')
                ->nullable()
                ->unique()
                ->constrained('guru', 'id_guru')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_siswa')
                ->nullable()
                ->unique()
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF',
            ])->default('AKTIF');

            $table->dateTime('last_login_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};