<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\MozoAuthController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// ====================
// ADMIN
// ====================

Route::get('/admin/login', [AdminLoginController::class, 'create'])
    ->name('admin.login');

Route::post('/admin/login', [AdminLoginController::class, 'store'])
    ->name('admin.login.store');

Route::middleware(EnsureAdministrator::class)->group(function () {

    Route::view('/admin/panel', 'admin.dashboard')
        ->name('admin.dashboard');

    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])
        ->name('admin.logout');
});


// ====================
// MOZO
// ====================

Route::get('/mozo/login', [MozoAuthController::class, 'index'])
    ->name('mozo.login');

Route::post('/mozo/login', [MozoAuthController::class, 'login'])
    ->name('mozo.login.process');

Route::post('/mozo/logout', [MozoAuthController::class, 'logout'])
    ->name('mozo.logout');

// Temporal: después lo reemplazaremos por el verdadero módulo Mesas
Route::get('/mesas', function () {
    return 'Acceso correcto. Bienvenido al módulo de mesas.';
})->name('mesas');