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
        Schema::create('kartu_rfid', function (Blueprint $table) {
            $table->id('id_kartu_rfid');

            $table->foreignId('id_siswa')
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('uid_rfid', 100)
                ->unique();

            $table->enum('status', [
                'AKTIF',
                'HILANG',
                'DIGANTI',
                'NONAKTIF'
            ])->default('AKTIF');

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai')
                ->nullable();

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->index(['id_siswa', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_rfid');
    }
};
