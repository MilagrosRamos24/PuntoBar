<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFluidezTest extends TestCase
{
    use RefreshDatabase;

    public function test_cambio_de_estado_json_devuelve_tarjeta_actualizada(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'estado' => 'activo']);
        $mozo = User::factory()->create(['role' => 'mozo', 'estado' => 'activo']);
        $mesa = Mesa::create([
            'numero' => 1, 'activa' => true, 'estado' => 'libre',
            'mozo_id' => $mozo->id, 'cantidad_personas' => 2,
        ]);

        $respuesta = $this->actingAs($admin)->putJson(route('mesas.estado', $mesa), ['estado' => 'ocupada'])
            ->assertOk()->assertJsonPath('mensaje', 'Estado actualizado correctamente.');
        $this->assertSame('ocupada', $mesa->fresh()->estado);
        $this->assertStringContainsString('data-mesa-id="'.$mesa->id.'"', $respuesta->json('html'));
        $this->assertStringContainsString('data-mesa-estado="En espera de pedido"', $respuesta->json('html'));
    }

    public function test_json_conserva_la_validacion_de_mozo_y_personas(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'estado' => 'activo']);
        $mesa = Mesa::create(['numero' => 1, 'activa' => true, 'estado' => 'libre', 'cantidad_personas' => 0]);

        foreach (Mesa::ESTADOS_CON_MOZO as $estado) {
            $this->actingAs($admin)->putJson(route('mesas.estado', $mesa), ['estado' => $estado])
                ->assertUnprocessable()->assertJsonValidationErrors('estado');
            $this->assertSame('libre', $mesa->fresh()->estado);
        }
    }

    public function test_json_no_permite_a_otro_mozo_modificar_la_mesa(): void
    {
        $titular = User::factory()->create(['role' => 'mozo', 'estado' => 'activo']);
        $otro = User::factory()->create(['role' => 'mozo', 'estado' => 'activo']);
        $mesa = Mesa::create([
            'numero' => 1, 'activa' => true, 'estado' => 'ocupada',
            'mozo_id' => $titular->id, 'cantidad_personas' => 2,
        ]);
        $this->actingAs($otro)->putJson(route('mesas.estado', $mesa), ['estado' => 'libre'])->assertForbidden();
        $this->assertSame('ocupada', $mesa->fresh()->estado);
    }

    public function test_formulario_sin_javascript_conserva_la_redireccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'estado' => 'activo']);
        $mesa = Mesa::create(['numero' => 1, 'activa' => true, 'estado' => 'libre', 'cantidad_personas' => 0]);
        $this->actingAs($admin)->put(route('mesas.estado', $mesa), ['estado' => 'libre'])
            ->assertRedirect(route('mesas'))->assertSessionHasNoErrors();
    }
}
