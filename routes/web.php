<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\MozoAuthController;
use App\Http\Middleware\VerificarRol;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\AdminMesaController;
use App\Http\Controllers\ComandaController;
use App\Http\Controllers\AdminProductoController;
use App\Http\Controllers\ActualizacionMasivaPreciosController;
use App\Http\Controllers\AdminComandaController;
use App\Http\Controllers\AdminMozoController;
use App\Http\Controllers\AdminDescuentoController;

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
    Route::post('/mesas/{mesa}/atender', [MesaController::class, 'atender'])
    ->name('mesas.atender');

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
    
    Route::get('/mesas/{mesa}', [MesaController::class, 'show'])
    ->name('mesas.show');
});

    // Comandas (PB-10 a PB-14)
    Route::post('/mesas/{mesa}/comandas', [ComandaController::class, 'store'])
        ->name('comandas.store');

    Route::get('/comandas/{comanda}', [ComandaController::class, 'show'])
        ->name('comandas.show');

    Route::post('/comandas/{comanda}/productos', [ComandaController::class, 'agregarProducto'])
        ->name('comandas.productos.store');

    Route::patch('/comandas/{comanda}/productos/{detalle}', [ComandaController::class, 'actualizarDetalle'])
        ->scopeBindings()
        ->name('comandas.productos.update');

    Route::delete('/comandas/{comanda}/productos/{detalle}', [ComandaController::class, 'quitarDetalle'])
        ->scopeBindings()
        ->name('comandas.productos.destroy');

    Route::post('/comandas/{comanda}/cerrar', [ComandaController::class, 'cerrar'])
        ->name('comandas.cerrar');
        
// ====================
// SOLO ADMINISTRADOR (Módulos III y IV, productos y descuento)
// ====================

Route::middleware(VerificarRol::class . ':admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('mesas', AdminMesaController::class);
        // Gestión de mozos.
        Route::patch('mozos/{mozo}/habilitar', [AdminMozoController::class, 'habilitar'])
        ->name('mozos.habilitar');
        Route::resource('mozos', AdminMozoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
                // Productos (catálogo, baja/alta y actualización masiva de precios)
        Route::post('productos/actualizacion-masiva/vista-previa', [ActualizacionMasivaPreciosController::class, 'vistaPrevia'])
            ->name('productos.masiva.vista-previa');

        Route::post('productos/actualizacion-masiva', [ActualizacionMasivaPreciosController::class, 'aplicar'])
            ->name('productos.masiva.aplicar');

        Route::patch('productos/{producto}/estado', [AdminProductoController::class, 'cambiarEstado'])
            ->name('productos.estado');

        Route::resource('productos', AdminProductoController::class)
            ->only(['index', 'store', 'update']);

        Route::get('/panel', function () {
    return redirect()->route('mesas');
})->name('dashboard');

        Route::post('/logout', [AdminLoginController::class, 'destroy'])
            ->name('logout');
            
         Route::get('/comandas', [AdminComandaController::class, 'index'])
        ->name('comandas.index');

         Route::get('/descuento', [AdminDescuentoController::class, 'edit'])
        ->name('descuento.edit');

    Route::put('/descuento', [AdminDescuentoController::class, 'update'])
        ->name('descuento.update');
    });