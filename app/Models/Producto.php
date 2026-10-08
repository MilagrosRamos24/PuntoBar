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