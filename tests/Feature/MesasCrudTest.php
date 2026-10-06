<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesasCrudTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'admin', string $estado = 'activo'): User
    {
        return User::factory()->create(['role' => $role, 'estado' => $estado]);
    }

    private function datos(array $cambios = []): array
    {
        return array_merge(['numero' => 1, 'estado' => 'libre', 'mozo_id' => null, 'cantidad_personas' => 0], $cambios);
    }

    public function test_administrador_puede_crear_consultar_modificar_y_eliminar(): void
    {
        $this->actingAs($this->usuario());
        $mozo = $this->usuario('mozo');
        $this->post('/admin/mesas', $this->datos())->assertSessionHasNoErrors();
        $mesa = Mesa::firstOrFail();
        $this->get('/admin/mesas/'.$mesa->id)->assertOk()->assertSee('Sin asignar');
        $this->put('/admin/mesas/'.$mesa->id, $this->datos(['numero' => 2, 'estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 4]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'numero' => 2, 'estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 4]);
        $this->get('/admin/mesas')->assertOk()->assertSee($mozo->name);
        $this->delete('/admin/mesas/'.$mesa->id)->assertRedirect('/admin/mesas');
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
    }

    public function test_rechaza_duplicados_personas_negativas_y_asignaciones_invalidas(): void
    {
        $this->actingAs($this->usuario());
        Mesa::create($this->datos());
        $inactivo = $this->usuario('mozo', 'inactivo');
        $admin = $this->usuario();
        $this->post('/admin/mesas', $this->datos())->assertSessionHasErrors('numero');
        foreach ([$inactivo->id, $admin->id, 999999] as $id) {
            $this->post('/admin/mesas', $this->datos(['numero' => 2, 'mozo_id' => $id]))->assertSessionHasErrors('mozo_id');
        }
        $this->post('/admin/mesas', $this->datos(['numero' => 2, 'cantidad_personas' => -1]))->assertSessionHasErrors('cantidad_personas');
    }

    public function test_mozo_no_puede_gestionar_mesas_del_administrador(): void
    {
        $mesa = Mesa::create($this->datos());
        $this->actingAs($this->usuario('mozo'));
        $this->get('/admin/mesas')->assertForbidden();
        $this->get('/admin/mesas/'.$mesa->id)->assertForbidden();
        $this->post('/admin/mesas', $this->datos(['numero' => 2]))->assertForbidden();
        $this->put('/admin/mesas/'.$mesa->id, $this->datos())->assertForbidden();
        $this->delete('/admin/mesas/'.$mesa->id)->assertForbidden();
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id]);
    }

   public function test_cuatro_estados_validos_y_rechazo_de_estado_inventado(): void
{
    $mozo = $this->usuario('mozo');

    $mesa = Mesa::create($this->datos());
    $mesa->forceFill(['mozo_id' => $mozo->id])->save();

    $this->actingAs($mozo);

    foreach (array_keys(Mesa::ESTADOS) as $estado) {
        $this->put('/mesas/'.$mesa->id.'/estado', ['estado' => $estado])
            ->assertRedirect(route('mesas'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => $estado]);
    }

    $this->put('/mesas/'.$mesa->id.'/estado', ['estado' => 'inventado'])
        ->assertSessionHasErrors('estado');

    $this->get('/mesas')->assertOk()->assertSee('Alerta de atención');
}

    public function test_editar_no_cambia_el_estado_anterior_ni_el_mozo_inactivo_sin_pedirlo(): void
    {
        $mozo = $this->usuario('mozo', 'inactivo');
        $mesa = Mesa::create($this->datos(['estado' => 'alerta', 'mozo_id' => $mozo->id]));
        $this->actingAs($this->usuario());
        $this->get('/admin/mesas/'.$mesa->id.'/edit')->assertOk()->assertSee('estado anterior');
        $this->put('/admin/mesas/'.$mesa->id, $this->datos(['estado' => 'alerta', 'mozo_id' => $mozo->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'alerta', 'mozo_id' => $mozo->id]);
        $this->get('/admin/mesas/999999')->assertNotFound();
    }
}
