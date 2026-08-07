<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\VectorDatabaseController;
use App\Http\Controllers\InvoiceChatController;

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
    Route::get('/invoices/upload', [InvoiceController::class, 'index'])->name('invoices.upload');
    Route::post('/invoices/process', [InvoiceController::class, 'process'])->name('invoices.process');
    Route::post('/invoices/confirm', [InvoiceController::class, 'confirm'])->name('invoices.confirm');
    Route::get('/vector-db', [VectorDatabaseController::class, 'index'])->name('vector-db.index');
    Route::get('/invoices/chat', [InvoiceChatController::class, 'index'])->name('invoices.chat');
    Route::post('/invoices/chat/ask', [InvoiceChatController::class, 'ask'])->name('invoices.chat.ask');
});

require __DIR__.'/auth.php';