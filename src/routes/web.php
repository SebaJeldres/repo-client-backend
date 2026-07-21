<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard accesible para usuarios autenticados y verificados
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas de gestión de perfil (para cualquier usuario autenticado)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --------------------------------------------------------------------------
// RUTAS PROTEGIDAS POR ROLES 
// --------------------------------------------------------------------------

// Rutas exclusivas para el Administrador
Route::middleware(['auth', 'role:admin'])->group(function () {
    
});

// Rutas compartidas (por ejemplo: Admin o Cliente)
Route::middleware(['auth', 'role:admin|cliente'])->group(function () {
    
});

require __DIR__.'/auth.php';