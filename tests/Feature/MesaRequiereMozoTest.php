<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesaRequiereMozoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'estado' => 'activo']);
    }

    private function mesa(array $cambios = []): Mesa
    {
        static $numero = 100;
        $numero++;

        return Mesa::create(array_merge([
            'numero'            => $numero,
            'estado'            => 'libre',
            'mozo_id'           => null,
            'cantidad_personas' => 0,
        ], $cambios));
    }

    /** El aviso que tiene que ver el administrador para cada estado. */
    private function mensaje(string $estado): string
    {
        return 'Asigná un mozo a la mesa antes de pasarla a «' . Mesa::ESTADOS[$estado] . '».';
    }

    /** Datos tal como los manda el formulario de edición, con los cambios que se quieran probar. */
    private function formulario(Mesa $mesa, array $cambios = []): array
    {
        return array_merge([
            'numero'            => $mesa->numero,
            'estado'            => $mesa->estado,
            'mozo_id'           => $mesa->mozo_id,
            'cantidad_personas' => $mesa->cantidad_personas,
        ], $cambios);
    }

    // ---------- Desde la pantalla de Mesas (el desplegable de cada tarjeta) ----------

    public function test_una_mesa_sin_mozo_no_pasa_a_espera_ni_a_alerta_desde_la_pantalla_de_mesas(): void
    {
        $mesa = $this->mesa();
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $estado) {
            $this->from(route('mesas'))
                ->put('/mesas/'.$mesa->id.'/estado', ['estado' => $estado])
                ->assertSessionHasErrors(['estado' => $this->mensaje($estado)]);

            $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre']);
        }
    }

    public function test_con_mozo_asignado_la_mesa_si_pasa_a_espera_y_a_alerta(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['mozo_id' => $mozo->id]);
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $estado) {
            $this->put('/mesas/'.$mesa->id.'/estado', ['estado' => $estado])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => $estado]);
        }
    }

    public function test_pasar_a_libre_no_exige_mozo(): void
    {
        $mesa = $this->mesa();

        $this->actingAs($this->usuario())
            ->put('/mesas/'.$mesa->id.'/estado', ['estado' => 'libre'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre']);
    }

    // ---------- Desde el formulario de alta ----------

    public function test_el_alta_rechaza_una_mesa_en_espera_o_en_alerta_sin_mozo(): void
    {
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $i => $estado) {
            $this->post('/admin/mesas', [
                'numero'            => 200 + $i,
                'estado'            => $estado,
                'mozo_id'           => null,
                'cantidad_personas' => 2,
            ])->assertSessionHasErrors(['mozo_id' => $this->mensaje($estado)]);
        }

        $this->assertSame(0, Mesa::count());
    }

    public function test_el_alta_acepta_el_estado_y_el_mozo_juntos(): void
    {
        $mozo = $this->usuario('mozo');

        $this->actingAs($this->usuario())
            ->post('/admin/mesas', [
                'numero'            => 300,
                'estado'            => 'ocupada',
                'mozo_id'           => $mozo->id,
                'cantidad_personas' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['numero' => 300, 'estado' => 'ocupada', 'mozo_id' => $mozo->id]);
    }

    // ---------- Desde el formulario de edición ----------

    public function test_editar_permite_poner_el_estado_y_el_mozo_en_el_mismo_guardado(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa();

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, [
                'estado'            => 'ocupada',
                'mozo_id'           => $mozo->id,
                'cantidad_personas' => 2,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'ocupada', 'mozo_id' => $mozo->id]);
    }

    public function test_editar_no_permite_sacar_el_mozo_a_una_mesa_en_espera(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 2]);

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, ['mozo_id' => null]))
            ->assertSessionHasErrors(['mozo_id' => $this->mensaje('ocupada')]);

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'ocupada', 'mozo_id' => $mozo->id]);
    }

    public function test_editar_permite_liberar_la_mesa_y_sacarle_el_mozo(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 2]);

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, [
                'estado'            => 'libre',
                'mozo_id'           => null,
                'cantidad_personas' => 0,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre', 'mozo_id' => null]);
    }
}