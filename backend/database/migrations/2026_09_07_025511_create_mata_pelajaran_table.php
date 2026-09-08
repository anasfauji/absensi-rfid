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
    Schema::create('mata_pelajaran', function (Blueprint $table) {
        $table->id('id_mata_pelajaran');

        $table->string('kode_mata_pelajaran', 30)
            ->unique();

        $table->string('nama_mata_pelajaran', 150);

        $table->string('kelompok', 50)
            ->nullable();

        $table->enum('status', [
            'AKTIF',
            'NONAKTIF'
        ])->default('AKTIF');

        $table->text('keterangan')
            ->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mata_pelajaran');
    }
};
