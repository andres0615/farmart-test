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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            // El médico es un usuario con rol "medico" (validado en la app).
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->date('valid_until');

            // La tabla solo tiene created_at: no usar $table->timestamps().
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
