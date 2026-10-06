<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Productos de ejemplo. Se puede ejecutar varias veces sin duplicar.
     */
    public function run(): void
    {
        $productos = [
            ['nombre' => 'Agua mineral', 'categoria' => 'bebida', 'precio' => 1500],
            ['nombre' => 'Gaseosa', 'categoria' => 'bebida', 'precio' => 2000],
            ['nombre' => 'Cerveza tirada', 'categoria' => 'bebida', 'precio' => 3500],
            ['nombre' => 'Fernet con cola', 'categoria' => 'bebida', 'precio' => 4500],
            ['nombre' => 'Papas fritas', 'categoria' => 'comida', 'precio' => 5000],
            ['nombre' => 'Hamburguesa completa', 'categoria' => 'comida', 'precio' => 9000],
            ['nombre' => 'Picada para dos', 'categoria' => 'comida', 'precio' => 14000],
            ['nombre' => 'Flan con dulce de leche', 'categoria' => 'postre', 'precio' => 4000],
        ];

        foreach ($productos as $producto) {
            Producto::firstOrCreate(
                ['nombre' => $producto['nombre']],
                $producto + ['estado' => 'activo']
            );
        }
    }
}
