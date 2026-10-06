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

    protected $fillable = ['nombre', 'categoria', 'precio', 'estado'];

    protected function casts(): array
    {
        return ['precio' => 'decimal:2'];
    }

    /**
     * Uso: Producto::activos()->get()
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
