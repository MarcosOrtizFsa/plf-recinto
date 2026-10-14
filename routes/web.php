<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Configuracion\DocumentoController;
use App\Http\Controllers\Configuracion\DocumentoSesionController;
use App\Http\Controllers\Configuracion\SesionParlamentariaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/inicio', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::resource('usuarios', UsuarioController::class)->except('show')->parameters(['usuarios' => 'usuario']);

    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
        Route::get('/documentos/nuevo', [DocumentoController::class, 'create'])->name('documentos.create');
        Route::post('/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
        Route::get('/documentos/{documento}/preview', [DocumentoController::class, 'preview'])->name('documentos.preview');
        Route::patch('/documentos/{documento}/estado', [DocumentoController::class, 'cambiarEstado'])->name('documentos.estado');
        Route::put('/sesiones/{sesion}/documentos/{documento}', [DocumentoSesionController::class, 'update'])->name('sesiones.documentos.update');
        Route::resource('sesiones', SesionParlamentariaController::class)->only(['index', 'create', 'store', 'edit', 'update'])->parameters(['sesiones' => 'sesion']);
    });
});
