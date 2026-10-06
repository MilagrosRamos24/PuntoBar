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

    protected $fillable = ['numero', 'estado', 'mozo_id', 'cantidad_personas'];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'cantidad_personas' => 'integer'];
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
}
