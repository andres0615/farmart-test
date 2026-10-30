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
        Schema::table('users', function (Blueprint $table) {
            // Nullable por portabilidad: SQLite no permite agregar una columna
            // NOT NULL sin valor por defecto. La obligatoriedad se valida en la app.
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->restrictOnDelete();

            $table->index('role_id', 'idx_users_role');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
