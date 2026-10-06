<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clase DetalleComanda: cada producto cargado en una comanda.
     * Composición: si se elimina la comanda, se eliminan sus detalles.
     */
    public function up(): void
    {
        Schema::create('detalle_comandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comandas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2); // precio al momento de cargarlo
            $table->decimal('total', 10, 2);
            $table->timestamps();

            // Un mismo producto aparece una sola vez por comanda; se suma la cantidad.
            $table->unique(['comanda_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_comandas');
    }
};
