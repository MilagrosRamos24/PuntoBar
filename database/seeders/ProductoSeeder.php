<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Productos de ejemplo. Se puede ejecutar varias veces sin duplicar.
     *
     * - tipo, categoría y bloqueo de actualización masiva: se dejan siempre
     *   como en este listado.
     * - precio y estado: solo se cargan al crear el producto.
     * - stock: solo se carga si el producto lleva stock y todavía no tiene,
     *   así volver a correr el seeder no reinicia el stock real.
     */
    public function run(): void
    {
        $productos = [
            ['nombre' => 'Agua mineral', 'categoria' => 'bebida', 'precio' => 1500, 'tipo' => 'unidad', 'stock' => 48, 'permite_actualizacion_masiva' => false],
            ['nombre' => 'Gaseosa', 'categoria' => 'bebida', 'precio' => 2000, 'tipo' => 'unidad', 'stock' => 36, 'permite_actualizacion_masiva' => false],
            ['nombre' => 'Cerveza tirada', 'categoria' => 'bebida', 'precio' => 3500],
            ['nombre' => 'Fernet con cola', 'categoria' => 'bebida', 'precio' => 4500],
            ['nombre' => 'Papas fritas', 'categoria' => 'comida', 'precio' => 5000],
            ['nombre' => 'Hamburguesa completa', 'categoria' => 'comida', 'precio' => 9000],
            ['nombre' => 'Picada para dos', 'categoria' => 'comida', 'precio' => 14000],
            ['nombre' => 'Flan con dulce de leche', 'categoria' => 'postre', 'precio' => 4000],
        ];

        foreach ($productos as $datos) {
            $producto = Producto::firstOrNew(['nombre' => $datos['nombre']]);

            $producto->categoria = $datos['categoria'];
            $producto->tipo = $datos['tipo'] ?? 'preparacion';
            $producto->permite_actualizacion_masiva = $datos['permite_actualizacion_masiva'] ?? true;

            if (! $producto->exists) {
                $producto->precio = $datos['precio'];
                $producto->estado = 'activo';
            }

            if ($producto->llevaStock() && $producto->stock === null) {
                $producto->stock = $datos['stock'] ?? 0;
            }

            $producto->save();
        }
    }
}