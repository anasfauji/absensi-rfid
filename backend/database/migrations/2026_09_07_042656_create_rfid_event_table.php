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
        Schema::create('rfid_event', function (Blueprint $table) {
            $table->id('id_rfid_event');

            $table->foreignId('id_perangkat_rfid')
                ->constrained('perangkat_rfid', 'id_perangkat_rfid')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_kartu_rfid')
                ->nullable()
                ->constrained('kartu_rfid', 'id_kartu_rfid')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_siswa')
                ->nullable()
                ->constrained('siswa', 'id_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('uid_rfid', 100);

            $table->dateTime('waktu_event');

            $table->dateTime('waktu_diterima_server');

            $table->enum('status_proses', [
                'DITERIMA',
                'DIPROSES',
                'SELESAI'
            ])->default('DITERIMA');

            $table->enum('hasil_event', [
                'VALID',
                'DUPLIKAT',
                'DITOLAK',
                'EVENT_ONLY'
            ])->default('VALID');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->index(['id_perangkat_rfid', 'waktu_event']);
            $table->index(['id_kartu_rfid', 'waktu_event']);
            $table->index(['id_siswa', 'waktu_event']);
            $table->index(['uid_rfid', 'waktu_event']);
            $table->index(['hasil_event', 'waktu_event']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_event');
    }
};
