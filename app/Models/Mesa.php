<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    public const ESTADOS = [
    'libre' => 'Libre',
    'ocupada' => 'En espera de pedido',
    'reservada' => 'Mesa atendida',
    'pendiente_de_cierre' => 'Alerta de atención',
];
    /**
     * Estados que significan "hay clientes esperando": solo tienen sentido
     * si la mesa tiene un mozo asignado que los atienda.
     */
    public const ESTADOS_CON_MOZO = ['ocupada', 'pendiente_de_cierre'];

    protected $fillable = [
    'activa',
    'numero',
    'estado',
    'mozo_id',
    'cantidad_personas',
    'inicio_espera',
    'ultimo_pedido_en',
    ];


    protected function casts(): array
    {
    return [
        'activa' => 'boolean',
        'numero' => 'integer',
        'cantidad_personas' => 'integer',
        'inicio_espera' => 'datetime',
        'ultimo_pedido_en' => 'datetime',
    ];
    }

    protected static function booted(): void
    {
        static::saving(function (Mesa $mesa) {
            if ($mesa->estado === 'libre' || $mesa->activa === false) {
                $mesa->ultimo_pedido_en = null;
            } elseif (
                $mesa->isDirty('estado') &&
                (! $mesa->exists || $mesa->getOriginal('estado') === 'libre')
            ) {
                $mesa->ultimo_pedido_en = now();
            }
        });
    }

    public function mozo()
    {
        return $this->belongsTo(User::class, 'mozo_id');
    }

        public function comandaAbierta()
    {
        return $this->hasOne(Comanda::class)->where('estado', 'abierta');
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst(str_replace('_', ' ', $this->estado));
    }
    /**
 * Espera del primer pedido.
 */
public function estaEsperandoPrimerPedido(): bool
{
    return $this->inicio_espera !== null
        && in_array(
            $this->estado,
            ['ocupada', 'pendiente_de_cierre'],
            true
        );
}

/**
 * Momento en que se cumplen los 25 minutos.
 */
public function getAlertaEsperaEnAttribute(): ?string
{
    if (! $this->estaEsperandoPrimerPedido()) {
        return null;
    }

    return $this->inicio_espera
        ->copy()
        ->addMinutes(25)
        ->toIso8601String();
}

/**
 * Estado para mostrar en pantalla.
 * Conserva los nombres existentes en la base.
 */
public function getEstadoVisualAttribute(): string
{
    if (
        $this->estaEsperandoPrimerPedido()
        && now()->greaterThanOrEqualTo(
            $this->inicio_espera->copy()->addMinutes(25)
        )
    ) {
        return 'pendiente_de_cierre';
    }

    return $this->estado;
}
}
