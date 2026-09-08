<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identitas_sekolah', function (Blueprint $table) {
            $table->id('id_identitas_sekolah');

            $table->foreignId('id_sekolah')
                ->constrained('sekolah', 'id_sekolah')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nama_sekolah', 200);
            $table->text('alamat');

            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('logo', 255)->nullable();

            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identitas_sekolah');
    }
};