<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AyudaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role): User
    {
        return User::factory()->create(['role' => $role, 'estado' => 'activo']);
    }

    public function test_el_manual_es_publico(): void
    {
        $this->get('/ayuda')->assertOk()->assertSee('Manual de usuario');
    }

    public function test_sin_sesion_se_muestra_el_manual_del_mozo(): void
    {
        $this->get('/ayuda')
            ->assertSee('Entender las mesas')
            ->assertDontSee('Gestionar mozos');
    }

    public function test_el_administrador_ve_su_manual_por_defecto(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get('/ayuda')
            ->assertOk()
            ->assertSee('Gestionar mozos')
            ->assertDontSee('Entender las mesas');
    }

    public function test_se_puede_elegir_el_rol_con_la_direccion(): void
    {
        $this->get('/ayuda?rol=administrador')->assertSee('Gestionar mozos');

        $this->actingAs($this->usuario('admin'))
            ->get('/ayuda?rol=mozo')
            ->assertSee('Entender las mesas')
            ->assertDontSee('Gestionar mozos');
    }

    public function test_un_rol_invalido_vuelve_al_predeterminado(): void
    {
        $this->get('/ayuda?rol=cualquiera')
            ->assertOk()
            ->assertSee('Entender las mesas');
    }

    public function test_el_inicio_enlaza_al_manual(): void
    {
        $this->get('/')->assertOk()->assertSee(route('ayuda'));
    }

    public function test_el_menu_de_mesas_enlaza_al_manual_para_admin_y_mozo(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/mesas')->assertOk()->assertSee(route('ayuda'));
        $this->actingAs($this->usuario('mozo'))->get('/mesas')->assertOk()->assertSee(route('ayuda'));
    }

    public function test_el_menu_del_administrador_incluye_ayuda_en_otras_pantallas(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get('/admin/productos')
            ->assertOk()
            ->assertSee(route('ayuda'));
    }
}