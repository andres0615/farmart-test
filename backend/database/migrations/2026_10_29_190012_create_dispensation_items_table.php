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
        Schema::create('dispensation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensation_id')->constrained('dispensations')->restrictOnDelete();
            $table->foreignId('prescription_item_id')->constrained('prescription_items')->restrictOnDelete();
            // Una dispensación puede tomar varios lotes del mismo producto.
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->integer('quantity');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispensation_items');
    }
};
