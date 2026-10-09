<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public const CATEGORIAS = [
        'bebida' => 'Bebidas',
        'comida' => 'Comidas',
        'postre' => 'Postres',
    ];

    /**
     * 'unidad' lleva control de stock; 'preparacion' no lo lleva
     * (se puede dar de baja, pero no se descuenta nada).
     */
    public const TIPOS = [
        'unidad'      => 'Por unidad',
        'preparacion' => 'Preparación',
    ];

    protected $fillable = [
        'nombre',
        'categoria',
        'tipo',
        'stock',
        'precio',
        'estado',
        'permite_actualizacion_masiva',
    ];

    protected function casts(): array
    {
        return [
            'precio'                       => 'decimal:2',
            'stock'                        => 'integer',
            'permite_actualizacion_masiva' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Una preparación nunca tiene stock, entre por donde entre el dato.
               static::saving(function (Producto $producto) {
            if ($producto->tipo === 'preparacion') {
                $producto->stock = null;
            }
        });

        // Si el stock llegó a cero (o se repuso), el aviso para el administrador se actualiza solo,
        // venga el cambio de donde venga.
        static::updated(function (Producto $producto) {
            NotificacionStock::sincronizar($producto);
        });
    }


    public function llevaStock(): bool
    {
        return $this->tipo === 'unidad';
    }

    /**
     * ¿Se puede cargar hoy en una comanda?
     * Tiene que estar activo y, si lleva stock, tener al menos una unidad.
     */
    public function estaDisponible(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        return ! $this->llevaStock() || $this->stock > 0;
    }

     /**
     * Descuenta stock al cargar el producto en una comanda.
     * Devuelve false si no alcanza. Las preparaciones no descuentan nada.
     * La verificación y el descuento se hacen en una sola consulta, así dos
     * mozos no pueden vender la misma última unidad al mismo tiempo.
     */
    public function descontarStock(int $cantidad): bool
    {
        if (! $this->llevaStock()) {
            return true;
        }

        $actualizados = static::whereKey($this->id)
            ->where('stock', '>=', $cantidad)
            ->decrement('stock', $cantidad);

               if ($actualizados > 0) {
            $this->stock -= $cantidad;

            // El descuento usa una consulta directa, que no dispara los eventos del modelo:
            // se avisa a mano para que, si el stock llegó a cero, se genere la notificación.
            NotificacionStock::sincronizarConBase($this->id);

            return true;
        }
        return false;
    }

    /**
     * Devuelve stock cuando se quita un producto o se baja su cantidad.
     */
    public function devolverStock(int $cantidad): void
    {
        if (! $this->llevaStock() || $cantidad <= 0) {
            return;
        }

               static::whereKey($this->id)->increment('stock', $cantidad);

        $this->stock += $cantidad;

        // Igual que arriba: si el producto estaba sin stock, se cierra su aviso.
        NotificacionStock::sincronizarConBase($this->id);
    }


    /**
     * Uso: Producto::activos()->get()
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Productos que el mozo puede seleccionar: activos y, si llevan stock, con unidades.
     * Uso: Producto::disponibles()->get()
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('estado', 'activo')
            ->where(function (Builder $q) {
                $q->where('tipo', 'preparacion')
                  ->orWhere('stock', '>', 0);
            });
    }

    /**
     * Productos que pueden recibir una actualización masiva de precios.
     * El bloqueo manda: si es false, nunca entra.
     */
    public function scopeAdmitenActualizacionMasiva(Builder $query): Builder
    {
        return $query->where('permite_actualizacion_masiva', true);
    }
}