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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('warehouses')->restrictOnDelete();
            // El catálogo de estados se modela con un enum PHP en la app.
            $table->string('status', 20)->default('BORRADOR');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();

            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
