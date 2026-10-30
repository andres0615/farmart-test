<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        // Catálogo de roles. La tabla se crea vacía: los registros van en un seeder.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
