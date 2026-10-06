<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleComanda extends Model
{
    protected $table = 'detalle_comandas';

    protected $fillable = ['comanda_id', 'producto_id', 'cantidad', 'precio_unitario', 'total'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Actualiza la cantidad y recalcula el total de la línea.
     */
    public function cambiarCantidad(int $cantidad): void
    {
        $this->cantidad = $cantidad;
        $this->total = round($cantidad * (float) $this->precio_unitario, 2);
        $this->save();
    }
}
