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
        Schema::create('dispensations', function (Blueprint $table) {
            $table->id();
            // Clave de idempotencia (RN-09).
            $table->string('idempotency_key')->unique();
            $table->foreignId('prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('dispensed_by')->constrained('users')->restrictOnDelete();
            // Regente que autoriza cuando el producto es de control especial.
            $table->foreignId('authorized_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispensations');
    }
};
