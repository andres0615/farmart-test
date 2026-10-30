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
        // Auditoría (RN-10): solo IDs y acciones, nunca datos personales.
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('entity');
            // Sin llave foránea a propósito: la entidad puede ser de cualquier tabla.
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
