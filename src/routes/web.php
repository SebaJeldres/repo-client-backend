<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

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

// Rutas exclusivas para el admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    
});

// Rutas compartidas (por ejemplo: Admin o manager)
Route::middleware(['auth', 'role:admin|manager'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});

require __DIR__.'/auth.php';