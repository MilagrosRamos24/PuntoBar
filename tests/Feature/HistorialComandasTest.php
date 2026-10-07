<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialComandasTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role, string $username, string $nombre = 'Usuario'): User
    {
        return User::factory()->create([
            'name' => $nombre,
            'username' => $username,
            'role' => $role,
            'estado' => 'activo',
        ]);
    }

    private function comanda(User $mozo, int $numeroMesa, string $estado, string $fecha, float $total = 0): Comanda
    {
        $mesa = Mesa::firstOrCreate(
            ['numero' => $numeroMesa],
            ['estado' => 'libre', 'cantidad_personas' => 0]
        );

        return Comanda::create([
            'mesa_id' => $mesa->id,
            'mozo_id' => $mozo->id,
            'fecha' => $fecha,
            'estado' => $estado,
            'subtotal' => $total,
            'descuento' => 0,
            'total' => $total,
            'cerrada_en' => $estado === 'cerrada' ? $fecha : null,
        ]);
    }

    public function test_el_administrador_ve_el_listado_con_mesa_mozo_estado_y_total(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $mozo = $this->usuario('mozo', 'ana', 'Ana');
        $comanda = $this->comanda($mozo, 3, 'cerrada', '2026-10-06 21:30:00', 12500);

        $this->actingAs($admin)
            ->get(route('admin.comandas.index'))
            ->assertOk()
            ->assertSee('Mesa 3')
            ->assertSee('Ana')
            ->assertSee('06/10/2026 21:30')
            ->assertSee('Cerrada')
            ->assertSee('$12.500')
            ->assertSee(route('comandas.show', $comanda));
    }

    public function test_un_mozo_no_puede_ver_el_historial(): void
    {
        $mozo = $this->usuario('mozo', 'ana');

        $this->actingAs($mozo)
            ->get(route('admin.comandas.index'))
            ->assertForbidden();
    }

    public function test_sin_sesion_redirige_al_login_del_administrador(): void
    {
        $this->get(route('admin.comandas.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_filtra_por_estado(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $mozo = $this->usuario('mozo', 'ana');
        $this->comanda($mozo, 1, 'abierta', '2026-10-06 21:00:00');
        $this->comanda($mozo, 2, 'cerrada', '2026-10-06 22:00:00');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index', ['estado' => 'cerrada']))
            ->assertOk()
            ->assertSee('Mesa 2')
            ->assertDontSee('Mesa 1');
    }

    public function test_filtra_por_fecha(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $mozo = $this->usuario('mozo', 'ana');
        $this->comanda($mozo, 1, 'cerrada', '2026-10-05 21:00:00');
        $this->comanda($mozo, 2, 'cerrada', '2026-10-06 21:00:00');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index', ['fecha' => '2026-10-05']))
            ->assertOk()
            ->assertSee('Mesa 1')
            ->assertDontSee('Mesa 2');
    }

    public function test_filtra_por_mozo(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $ana = $this->usuario('mozo', 'ana', 'Ana');
        $luis = $this->usuario('mozo', 'luis', 'Luis');
        $this->comanda($ana, 1, 'cerrada', '2026-10-06 21:00:00');
        $this->comanda($luis, 2, 'cerrada', '2026-10-06 21:00:00');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index', ['mozo_id' => $luis->id]))
            ->assertOk()
            ->assertSee('Mesa 2')
            ->assertDontSee('Mesa 1');
    }

    public function test_las_comandas_mas_recientes_aparecen_primero(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $mozo = $this->usuario('mozo', 'ana');
        $this->comanda($mozo, 1, 'cerrada', '2026-10-05 21:00:00');
        $this->comanda($mozo, 2, 'cerrada', '2026-10-06 21:00:00');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index'))
            ->assertSeeInOrder(['Mesa 2', 'Mesa 1']);
    }

    public function test_un_estado_invalido_no_se_acepta_como_filtro(): void
    {
        $admin = $this->usuario('admin', 'admin');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index', ['estado' => 'inventado']))
            ->assertSessionHasErrors('estado');
    }

    public function test_muestra_un_mensaje_cuando_no_hay_comandas(): void
    {
        $admin = $this->usuario('admin', 'admin');

        $this->actingAs($admin)
            ->get(route('admin.comandas.index'))
            ->assertOk()
            ->assertSee('Todavía no hay comandas registradas.');
    }
}
