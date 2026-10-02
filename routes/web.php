<?php
use App\Http\Controllers\AdminLoginController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;
Route::view('/', 'home')->name('home');
Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
Route::middleware(EnsureAdministrator::class)->group(function () {
    Route::view('/admin/panel', 'admin.dashboard')->name('admin.dashboard');
    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');
});
