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
        Schema::create('kalender', function (Blueprint $table) {
            $table->id('id_kalender');

            $table->foreignId('id_tahun_ajaran')
                ->constrained('tahun_ajaran', 'id_tahun_ajaran')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('tanggal');

            $table->enum('status_hari', [
                'AKTIF',
                'TIDAK_AKTIF'
            ])->default('AKTIF');

            $table->string('jenis_kegiatan', 50)
                ->nullable();

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'id_tahun_ajaran',
                'tanggal'
            ]);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kalender');
    }
};
