<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');

            $table->foreignId('id_pengguna')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('judul', 150);

            $table->text('pesan');

            $table->string('jenis', 50);

            $table->string('referensi_tabel', 100)
                ->nullable();

            $table->string('referensi_id', 100)
                ->nullable();

            $table->dateTime('dibaca_at')
                ->nullable();

            $table->dateTime('created_at');

            $table->index(['id_pengguna', 'dibaca_at']);
            $table->index(['jenis', 'created_at']);
            $table->index(['referensi_tabel', 'referensi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
