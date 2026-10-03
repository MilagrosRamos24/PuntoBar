<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role): User
    {
        return User::factory()->create([
            'username' => $role . '_prueba',
            'role' => $role,
            'estado' => 'activo',
        ]);
    }

    public function test_mozo_cierra_sesion_y_vuelve_al_login(): void
    {
        $this->actingAs($this->usuario('mozo'))
            ->post('/mozo/logout')
            ->assertRedirect(route('mozo.login'));

        $this->assertGuest();
    }

    public function test_cerrar_sesion_limpia_los_datos_de_la_sesion(): void
    {
        $this->actingAs($this->usuario('mozo'))
            ->withSession(['dato_de_prueba' => 'valor'])
            ->post('/mozo/logout');

        $this->assertFalse(session()->has('dato_de_prueba'));
    }

    public function test_despues_de_cerrar_sesion_el_mozo_no_accede_a_mesas(): void
    {
        $this->actingAs($this->usuario('mozo'))->post('/mozo/logout');

        $this->get('/mesas')->assertRedirect(route('home'));
    }

    public function test_despues_de_cerrar_sesion_el_admin_no_accede_al_panel(): void
    {
        $this->actingAs($this->usuario('admin'))->post('/admin/logout');

        $this->get('/admin/panel')->assertRedirect(route('admin.login'));
    }

    public function test_la_pantalla_de_mesas_muestra_el_boton_de_cerrar_sesion(): void
    {
        $this->actingAs($this->usuario('mozo'))
            ->get('/mesas')
            ->assertOk()
            ->assertSee('Cerrar sesión')
            ->assertSee(route('mozo.logout'));
    }

    public function test_el_admin_en_mesas_usa_su_propio_logout(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get('/mesas')
            ->assertOk()
            ->assertSee(route('admin.logout'));
    }

    public function test_las_paginas_protegidas_no_se_guardan_en_cache(): void
    {
        $respuesta = $this->actingAs($this->usuario('mozo'))->get('/mesas');

        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_el_mozo_no_puede_usar_el_logout_del_administrador(): void
    {
        $this->actingAs($this->usuario('mozo'))
            ->post('/admin/logout')
            ->assertForbidden();
    }
}
