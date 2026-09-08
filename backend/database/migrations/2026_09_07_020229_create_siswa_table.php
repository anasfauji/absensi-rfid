<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id('id_siswa');

            $table->string('nis', 30)
                ->unique();

            $table->string('nisn', 30)
                ->nullable()
                ->unique();

            $table->string('nama_siswa', 150);

            $table->enum('jenis_kelamin', [
                'L',
                'P',
            ]);

            $table->string('tempat_lahir', 100)
                ->nullable();

            $table->date('tanggal_lahir')
                ->nullable();

            $table->string('nomor_telepon', 30)
                ->nullable();

            $table->text('alamat')
                ->nullable();

            $table->string('foto', 255)
                ->nullable();

            $table->enum('status', [
                'AKTIF',
                'LULUS',
                'PINDAH',
                'NONAKTIF',
            ])->default('AKTIF');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};