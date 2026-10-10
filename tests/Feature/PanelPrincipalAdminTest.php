<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPrincipalAdminTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role): User
    {
        return User::factory()->create(['role' => $role, 'estado' => 'activo']);
    }

    /** Crea un producto con una sola unidad y la "vende" por el mismo camino que usan las comandas. */
    private function agotar(string $nombre): Producto
    {
        $producto = Producto::create([
            'nombre'                       => $nombre,
            'categoria'                    => 'bebida',
            'tipo'                         => 'unidad',
            'stock'                        => 1,
            'precio'                       => 1500,
            'estado'                       => 'activo',
            'permite_actualizacion_masiva' => true,
        ]);

        $producto->descontarStock(1);

        return $producto;
    }

    public function test_el_panel_principal_del_administrador_muestra_la_campana_y_el_aviso(): void
    {
        $this->agotar('Gaseosa de prueba');
        $this->actingAs($this->usuario('admin'));

        $this->get('/mesas')
            ->assertOk()
            ->assertSee('Notificaciones: 1 sin leer')
            ->assertSee('Tenés 1 notificación sin leer')
            ->assertSee('Ver notificaciones');
    }

    public function test_el_panel_principal_no_muestra_nada_al_mozo(): void
    {
        $this->agotar('Gaseosa de prueba');
        $this->actingAs($this->usuario('mozo'));

        $this->get('/mesas')
            ->assertOk()
            ->assertDontSee('Tenés 1 notificación sin leer')
            ->assertDontSee('pn-campana');
    }
}