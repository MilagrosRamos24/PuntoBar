<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class AdminLoginTest extends TestCase {
    use RefreshDatabase;
    private function administrator(string $role = 'admin'): User {
        $user = User::factory()->create(['password' => Hash::make('ClavePrueba123!')]);
        $user->username = 'admin'; $user->role = $role; $user->save();
        return $user;
    }
    public function test_form_is_available(): void {
        $this->get('/admin/login')->assertOk()->assertSee('Usuario')->assertSee('Contraseña');
    }

    public function test_admin_can_login_and_view_panel(): void {
     $user = $this->administrator();
        $this->post('/admin/login', ['username'=>'ADMIN', 'password'=>'ClavePrueba123!'])->assertRedirect(route('mesas'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('mesas'))->assertOk()->assertSee('Vista de administrador');
        $this->get('/admin/panel')->assertRedirect(route('mesas'));
}
    public function test_incorrect_password_is_rejected(): void {
        $this->administrator();
        $this->from('/admin/login')->post('/admin/login', ['username'=>'admin','password'=>'incorrecta'])->assertRedirect('/admin/login')->assertSessionHasErrors('username')->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }
    public function test_unknown_user_is_rejected(): void {
        $this->post('/admin/login', ['username'=>'inexistente','password'=>'incorrecta'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    public function test_required_fields_are_validated(): void {
        $this->post('/admin/login', [])->assertSessionHasErrors(['username','password']);
    }
    public function test_waiter_cannot_login_as_admin(): void {
        $this->administrator('mozo');
        $this->post('/admin/login', ['username'=>'admin','password'=>'ClavePrueba123!'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    public function test_panel_is_protected(): void {
        $this->get('/admin/panel')->assertRedirect('/admin/login');
        $this->actingAs($this->administrator('mozo'))->get('/admin/panel')->assertForbidden();
    }
    public function test_logout_ends_access(): void {
        $this->actingAs($this->administrator())->post('/admin/logout')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get('/admin/panel')->assertRedirect('/admin/login');
    }
    public function test_failed_attempts_are_limited(): void {
        for ($i=0;$i<5;$i++) {
            $this->post('/admin/login', ['username'=>'bloqueado','password'=>'incorrecta']);
        }
        $this->post('/admin/login', ['username'=>'bloqueado','password'=>'incorrecta'])->assertSessionHasErrors('username');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('username'));
    }
}
