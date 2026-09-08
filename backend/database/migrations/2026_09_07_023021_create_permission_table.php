<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission', function (Blueprint $table) {
            $table->id('id_permission');

            $table->string('kode_permission', 100)
                ->unique();

            $table->string('nama_permission', 150);

            $table->string('modul', 100);

            $table->string('aksi', 50);

            $table->text('deskripsi')
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
        Schema::dropIfExists('permission');
    }
};