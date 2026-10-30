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
        // Kardex (RN-06). Registro append-only: los triggers de inmutabilidad
        // se crean en la migración 18.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->string('type', 25);
            // Con signo: positivo entra, negativo sale.
            $table->integer('quantity');
            // Obligatorio cuando type = 'AJUSTE'.
            $table->text('reason')->nullable();
            $table->foreignId('dispensation_id')->nullable()->constrained('dispensations')->restrictOnDelete();
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->timestampTz('created_at')->useCurrent();

            $table->index(['warehouse_id', 'batch_id', 'created_at'], 'idx_movements_lookup');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
