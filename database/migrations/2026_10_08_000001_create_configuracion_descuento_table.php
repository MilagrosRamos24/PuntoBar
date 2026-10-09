<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clase ConfiguracionDescuento: tope y porcentaje del descuento por consumo.
     * Hay una sola configuración para todo el bar.
     */
    public function up(): void
    {
        Schema::create('configuracion_descuento', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto_tope', 10, 2)->default(0);
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_descuento');
    }
};
