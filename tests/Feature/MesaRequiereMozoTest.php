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

    /** El aviso por falta de mozo, igual en la pantalla de Mesas y en el formulario. */
    private function mensaje(string $estado): string
    {
        return 'Asigná un mozo a la mesa antes de pasarla a «' . Mesa::ESTADOS[$estado] . '».';
    }

    /** El aviso por falta de personas en el desplegable de la pantalla de Mesas. */
    private function mensajePersonasTarjeta(string $estado): string
    {
        return 'Indicá cuántas personas hay en la mesa antes de pasarla a «' . Mesa::ESTADOS[$estado] . '»: usá «Registrar atención» (mozo) o «Editar» (administrador).';
    }

    /** El aviso por falta de personas en el formulario de crear y editar mesa. */
    private function mensajePersonasFormulario(string $estado): string
    {
        return 'Indicá cuántas personas hay en la mesa (al menos 1) antes de pasarla a «' . Mesa::ESTADOS[$estado] . '».';
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

    // ---------- Falta el mozo: desde la pantalla de Mesas (el desplegable de cada tarjeta) ----------

    public function test_una_mesa_sin_mozo_no_pasa_a_espera_ni_a_alerta_desde_la_pantalla_de_mesas(): void
    {
        $mesa = $this->mesa(['cantidad_personas' => 2]);
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $estado) {
            $this->from(route('mesas'))
                ->put('/mesas/'.$mesa->id.'/estado', ['estado' => $estado])
                ->assertSessionHasErrors(['estado' => $this->mensaje($estado)]);

            $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre']);
        }
    }

    public function test_con_mozo_y_personas_la_mesa_si_pasa_a_espera_y_a_alerta(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['mozo_id' => $mozo->id, 'cantidad_personas' => 2]);
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

    // ---------- Falta el mozo: formularios de alta y de edición ----------

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

    public function test_el_alta_acepta_el_estado_el_mozo_y_las_personas_juntos(): void
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

    public function test_editar_permite_poner_el_estado_el_mozo_y_las_personas_en_el_mismo_guardado(): void
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

    // ---------- Falta la cantidad de personas ----------

    public function test_una_mesa_sin_personas_no_pasa_a_espera_ni_a_alerta_desde_la_pantalla_de_mesas(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['mozo_id' => $mozo->id, 'cantidad_personas' => 0]);
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $estado) {
            $this->from(route('mesas'))
                ->put('/mesas/'.$mesa->id.'/estado', ['estado' => $estado])
                ->assertSessionHasErrors(['estado' => $this->mensajePersonasTarjeta($estado)]);

            $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre']);
        }
    }

    public function test_el_alta_rechaza_una_mesa_en_espera_o_en_alerta_sin_personas(): void
    {
        $mozo = $this->usuario('mozo');
        $this->actingAs($this->usuario());

        foreach (['ocupada', 'pendiente_de_cierre'] as $i => $estado) {
            $this->post('/admin/mesas', [
                'numero'            => 400 + $i,
                'estado'            => $estado,
                'mozo_id'           => $mozo->id,
                'cantidad_personas' => 0,
            ])->assertSessionHasErrors(['cantidad_personas' => $this->mensajePersonasFormulario($estado)]);
        }

        $this->assertSame(0, Mesa::count());
    }

    public function test_editar_no_permite_dejar_sin_personas_una_mesa_en_espera(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 2]);

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, ['cantidad_personas' => 0]))
            ->assertSessionHasErrors(['cantidad_personas' => $this->mensajePersonasFormulario('ocupada')]);

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'ocupada', 'cantidad_personas' => 2]);
    }

    public function test_una_mesa_en_espera_sin_personas_se_corrige_liberandola_y_registrando_la_atencion(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa(['estado' => 'ocupada', 'mozo_id' => $mozo->id, 'cantidad_personas' => 0]);

        $this->actingAs($mozo);

        // Registrar atención no sirve: la mesa no está libre.
        $this->post(route('mesas.atender', $mesa), ['cantidad_personas' => 5])
            ->assertSessionHasErrors('mesa');

        // Se la pasa a Libre con el desplegable y después sí se registra la atención.
        $this->put('/mesas/'.$mesa->id.'/estado', ['estado' => 'libre'])
            ->assertSessionHasNoErrors();

        $this->post(route('mesas.atender', $mesa), ['cantidad_personas' => 5])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', [
            'id'                => $mesa->id,
            'estado'            => 'ocupada',
            'mozo_id'           => $mozo->id,
            'cantidad_personas' => 5,
        ]);
    }
        // ---------- El administrador asigna mozo y personas a una mesa libre ----------

    public function test_editar_una_mesa_libre_con_mozo_y_personas_la_pasa_a_espera(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa();

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, [
                'mozo_id'           => $mozo->id,
                'cantidad_personas' => 3,
            ]))
            ->assertSessionHasNoErrors();

        $mesa->refresh();
        $this->assertSame('ocupada', $mesa->estado);
        $this->assertNotNull($mesa->inicio_espera);
    }

    public function test_editar_una_mesa_libre_con_mozo_pero_sin_personas_sigue_libre(): void
    {
        $mozo = $this->usuario('mozo');
        $mesa = $this->mesa();

        $this->actingAs($this->usuario())
            ->put('/admin/mesas/'.$mesa->id, $this->formulario($mesa, ['mozo_id' => $mozo->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre', 'mozo_id' => $mozo->id]);
    }
}