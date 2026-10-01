<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->foreignId('id_guru_penangan')
                ->nullable()
                ->after('id_guru')
                ->constrained('guru', 'id_guru')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('id_guru_penangan');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->dropForeign([
                'id_guru_penangan',
            ]);

            $table->dropIndex([
                'id_guru_penangan',
            ]);

            $table->dropColumn('id_guru_penangan');
        });
    }
};