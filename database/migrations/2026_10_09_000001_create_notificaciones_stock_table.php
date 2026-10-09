<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avisos de "producto sin stock" para el administrador.
     *
     * Cada fila es un evento: el momento en que un producto se quedó sin stock.
     * - episodio_abierto vale 1 mientras el producto SIGUE sin stock y es NULL cuando
     *   ya se repuso. Con el índice único (producto_id, episodio_abierto) la base de datos
     *   impide dos avisos abiertos del mismo producto, aunque dos procesos lo intenten a la vez.
     *   (Los NULL no cuentan como repetidos, así que el historial puede tener todos los
     *   avisos cerrados que haga falta.)
     * - leida_at es NULL hasta que el administrador la marca como leída.
     * - nombre_producto conserva el nombre tal como era en ese momento.
     * - producto_id pasa a NULL si algún día se borrara el producto: el historial queda igual.
     */
    public function up(): void
    {
        Schema::create('notificaciones_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre_producto', 100);
            $table->timestamp('sin_stock_at');
            $table->timestamp('repuesto_at')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->unsignedTinyInteger('episodio_abierto')->nullable();
            $table->timestamps();

            $table->unique(['producto_id', 'episodio_abierto'], 'notif_stock_un_aviso_abierto');
            $table->index(['leida_at', 'sin_stock_at'], 'notif_stock_no_leidas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_stock');
    }
};