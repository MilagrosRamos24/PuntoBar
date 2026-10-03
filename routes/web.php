<?php
use App\Http\Controllers\AdminLoginController;
use App\Http\Middleware\VerificarRol;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
/*
|--------------------------------------------------------------------------
| Ingreso al sistema (sin sesión iniciada)
|--------------------------------------------------------------------------
| El límite de intentos fallidos del administrador ya lo maneja
| AdminLoginController (5 intentos por usuario/IP).
*/

Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
// Login del mozo (lo agrega Mili). Ejemplo:
// Route::get('/mozo/login', [MozoLoginController::class, 'create'])->name('mozo.login');
// Route::post('/mozo/login', [MozoLoginController::class, 'store'])->name('mozo.login.store');

/*
|--------------------------------------------------------------------------
| Módulo II: Mesas (administrador y mozo)
|--------------------------------------------------------------------------
*/

Route::middleware(VerificarRol::class . ':admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/panel', 'admin.dashboard')->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

        // A medida que se agreguen módulos:
        // Route::resource('mozos', MozoController::class);
        // Route::resource('productos', ProductoController::class);
});
