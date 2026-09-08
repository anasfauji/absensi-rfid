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
        Schema::create('perangkat_rfid', function (Blueprint $table) {
            $table->id('id_perangkat_rfid');

            $table->string('kode_perangkat', 50)
                ->unique();

            $table->string('nama_perangkat', 100);

            $table->string('lokasi', 150);

            $table->string('jenis_perangkat', 50);

            $table->enum('status', [
                'AKTIF',
                'NONAKTIF'
            ])->default('AKTIF');

            $table->dateTime('terakhir_terhubung_at')
                ->nullable();

            $table->timestamps();

            $table->index(['status']);
            $table->index(['lokasi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perangkat_rfid');
    }
};
