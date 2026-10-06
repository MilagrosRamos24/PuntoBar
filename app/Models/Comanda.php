<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comanda extends Model
{
    /**
     * Estados según la documentación. Si más adelante se suman estados
     * intermedios (en preparación, lista, entregada), se agregan acá.
     */
    public const ESTADOS = [
        'abierta' => 'Abierta',
        'cerrada' => 'Cerrada',
    ];

    protected $fillable = [
        'mesa_id', 'mozo_id', 'fecha', 'estado',
        'subtotal', 'descuento', 'total', 'cerrada_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'cerrada_en' => 'datetime',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mozo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mozo_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleComanda::class);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', 'abierta');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    /**
     * Recalcula subtotal, descuento y total a partir de los detalles.
     */
    public function recalcularTotales(): void
    {
        $subtotal = round((float) $this->detalles()->sum('total'), 2);
        $descuento = $this->calcularDescuento($subtotal);

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => round($subtotal - $descuento, 2),
        ]);
    }

    /**
     * Descuento por consumo (según la documentación: porcentaje sobre el total
     * cuando el subtotal supera un tope definido por el administrador).
     * Por ahora devuelve 0: se completa cuando exista ConfiguracionDescuento.
     */
    protected function calcularDescuento(float $subtotal): float
    {
        return 0.0;
    }
}
