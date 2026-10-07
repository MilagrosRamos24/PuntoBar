<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->enum('tipo', ['unidad', 'preparacion'])
                ->default('preparacion')
                ->after('categoria');
            $table->unsignedInteger('stock')
                ->nullable()
                ->after('tipo');
            $table->boolean('permite_actualizacion_masiva')
                ->default(true)
                ->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'stock', 'permite_actualizacion_masiva']);
        });
    }
};