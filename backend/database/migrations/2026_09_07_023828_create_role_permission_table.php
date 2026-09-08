<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id('id_role_permission');

            $table->foreignId('id_role')
                ->constrained('role', 'id_role')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_permission')
                ->constrained('permission', 'id_permission')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'id_role',
                'id_permission',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};