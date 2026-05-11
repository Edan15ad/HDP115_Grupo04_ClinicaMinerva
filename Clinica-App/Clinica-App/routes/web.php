<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ControladorUsuarioRegistrado;

// Pantalla de Bienvenida
Route::get('/', function () {
    return inertia('Welcome');
})->name('home');

// Rutas de Registro Personalizadas (Solo Invitados)
Route::middleware('guest')->group(function () {
    Route::get('/register', [ControladorUsuarioRegistrado::class, 'create'])->name('register');
    Route::post('/register', [ControladorUsuarioRegistrado::class, 'store']);
});

// Rutas Protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';