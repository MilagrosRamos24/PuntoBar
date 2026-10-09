<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionDescuento extends Model
{
    protected $table = 'configuracion_descuento';

    protected $fillable = ['monto_tope', 'porcentaje', 'activo'];

    protected function casts(): array
    {
        return [
            'monto_tope' => 'decimal:2',
            'porcentaje' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * Devuelve la configuración del bar. Si todavía no existe, la crea desactivada.
     */
    public static function actual(): self
    {
        return static::query()->first()
            ?? static::create(['monto_tope' => 0, 'porcentaje' => 0, 'activo' => false]);
    }

    /**
     * Descuento que corresponde a un subtotal.
     * Se aplica solo si está activo y el subtotal SUPERA el tope.
     */
    public function calcularDescuento(float $subtotal): float
    {
        if (! $this->activo || $subtotal <= (float) $this->monto_tope) {
            return 0.0;
        }

        return round($subtotal * (float) $this->porcentaje / 100, 2);
    }
}
