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
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('id_audit_log');

            $table->foreignId('id_pengguna')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('aksi', 50);

            $table->string('nama_tabel', 100);

            $table->string('id_data', 100);

            $table->json('data_sebelum')
                ->nullable();

            $table->json('data_sesudah')
                ->nullable();

            $table->text('alasan')
                ->nullable();

            $table->string('alamat_ip', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->dateTime('created_at');

            $table->index(['id_pengguna', 'created_at']);
            $table->index(['nama_tabel', 'id_data']);
            $table->index(['aksi', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
