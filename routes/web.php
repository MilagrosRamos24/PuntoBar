<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\MozoAuthController;
use App\Http\Middleware\VerificarRol;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\AdminMesaController;

Route::view('/', 'home')->name('home');

// ====================
// INGRESO (sin sesión iniciada)
// ====================

// Administrador. El límite de intentos lo maneja AdminLoginController.
Route::get('/admin/login', [AdminLoginController::class, 'create'])
    ->name('admin.login');

Route::post('/admin/login', [AdminLoginController::class, 'store'])
    ->name('admin.login.store');

// Mozo. throttle:5,1 = máximo 5 intentos por minuto, para que no se puedan
// adivinar las contraseñas probando combinaciones.
Route::get('/mozo/login', [MozoAuthController::class, 'index'])
    ->name('mozo.login');

Route::post('/mozo/login', [MozoAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('mozo.login.process');

// ====================
// SOLO MOZO
// ====================

Route::middleware(VerificarRol::class . ':mozo')->group(function () {

    Route::post('/mozo/logout', [MozoAuthController::class, 'logout'])
        ->name('mozo.logout');
});

// ====================
// ADMINISTRADOR Y MOZO (Módulo II: Mesas)
// ====================

Route::middleware(VerificarRol::class . ':admin,mozo')->group(function () {

    Route::get('/mesas', [MesaController::class, 'index'])
    ->name('mesas');

    Route::put('/mesas/{mesa}/estado', [MesaController::class, 'cambiarEstado'])
    ->name('mesas.estado');
});

// ====================
// SOLO ADMINISTRADOR (Módulos III y IV, productos y descuento)
// ====================

Route::middleware(VerificarRol::class . ':admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('mesas', AdminMesaController::class)
            ->except(['show']);

        Route::get('/panel', function () {
    return redirect()->route('mesas');
})->name('dashboard');

        Route::post('/logout', [AdminLoginController::class, 'destroy'])
            ->name('logout');
    });