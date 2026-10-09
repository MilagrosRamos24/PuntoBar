<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionStock extends Model
{
    protected $table = 'notificaciones_stock';

    protected $fillable = [
        'producto_id',
        'nombre_producto',
        'sin_stock_at',
        'repuesto_at',
        'leida_at',
        'episodio_abierto',
    ];

    protected function casts(): array
    {
        return [
            'sin_stock_at' => 'datetime',
            'repuesto_at'  => 'datetime',
            'leida_at'     => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->whereNull('leida_at');
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('sin_stock_at')->orderByDesc('id');
    }

    public function estaLeida(): bool
    {
        return $this->leida_at !== null;
    }

    /** Verdadero mientras el producto de este aviso sigue sin stock. */
    public function sigueSinStock(): bool
    {
        return $this->episodio_abierto !== null;
    }

    /**
     * Marca el aviso como leído. Solo toca la fecha de lectura: no cambia el stock del
     * producto y el aviso se queda en el historial. Si ya estaba leído, no hace nada.
     */
    public function marcarLeida(): void
    {
        static::whereKey($this->getKey())
            ->whereNull('leida_at')
            ->update(['leida_at' => now()]);

        $this->refresh();
    }

    /**
     * Camino 1: cambios hechos con el modelo (por ejemplo, el administrador editando el
     * producto). Se llama desde el evento "updated" de Producto.
     *
     * - El producto pasó de tener stock a quedarse en cero -> abre UN aviso.
     * - El producto vuelve a tener stock (o deja de llevar stock) -> cierra el aviso abierto.
     */
    public static function sincronizar(Producto $producto): void
    {
        if (! $producto->wasChanged(['stock', 'tipo'])) {
            return;
        }

        $seQuedoSinStock = $producto->llevaStock()
            && $producto->stock === 0
            && (int) $producto->getOriginal('stock') > 0;

        if ($seQuedoSinStock) {
            static::abrirAviso($producto);

            return;
        }

        if (! $producto->llevaStock() || $producto->stock > 0) {
            static::cerrarAviso($producto->id);
        }
    }

    /**
     * Camino 2: cambios hechos con una consulta directa (descontarStock y devolverStock de
     * Producto, que usan decrement/increment). Esas consultas no disparan los eventos del
     * modelo, así que se llama a mano después de ellas. Lee el stock real de la base, por lo
     * que sirve sin importar cómo se guardó el dato.
     */
    public static function sincronizarConBase(int $productoId): void
    {
        $producto = Producto::find($productoId);

        if ($producto === null) {
            return;
        }

        if ($producto->llevaStock() && $producto->stock === 0) {
            static::abrirAviso($producto);
        } else {
            static::cerrarAviso($producto->id);
        }
    }

    /**
     * Abre el aviso de un agotamiento. Si ya hay uno abierto no crea otro, y si dos procesos
     * lo intentan a la vez el índice único de la base hace que gane uno solo.
     */
    private static function abrirAviso(Producto $producto): void
    {
        static::createOrFirst(
            ['producto_id' => $producto->id, 'episodio_abierto' => 1],
            ['nombre_producto' => $producto->nombre, 'sin_stock_at' => now()]
        );
    }

    /** Cierra el aviso abierto y anota cuándo se repuso. El aviso nunca se borra. */
    private static function cerrarAviso(int $productoId): void
    {
        static::where('producto_id', $productoId)
            ->where('episodio_abierto', 1)
            ->update(['episodio_abierto' => null, 'repuesto_at' => now()]);
    }
}