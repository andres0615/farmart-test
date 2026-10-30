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
        // Inventario por bodega + lote (RN-01). El producto se deduce del lote.
        Schema::create('stock', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->integer('quantity')->default(0);

            // Llave primaria compuesta: no se usa $table->id().
            $table->primary(['warehouse_id', 'batch_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
