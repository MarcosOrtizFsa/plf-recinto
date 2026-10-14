<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BancaController;
use App\Http\Controllers\ComandoController;
use App\Http\Controllers\Configuracion\DocumentoController;
use App\Http\Controllers\Configuracion\DocumentoSesionController;
use App\Http\Controllers\Configuracion\SesionParlamentariaController;
use App\Http\Controllers\LatidoController;
use App\Http\Controllers\MonitorPublicoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::get('/monitor', [MonitorPublicoController::class, 'show'])->name('monitor.show');
Route::get('/monitor/estado', [MonitorPublicoController::class, 'estado'])->name('monitor.estado');
Route::get('/latido', LatidoController::class)->name('latido');

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

    Route::prefix('comando')->name('comando.')->group(function () {
        Route::get('/', [ComandoController::class, 'index'])->name('index');
        Route::get('/estado', [ComandoController::class, 'estado'])->name('estado');
        Route::post('/sesiones/{sesion}/activar', [ComandoController::class, 'activarSesion'])->name('sesiones.activar');
        Route::post('/sesiones/{sesion}/cerrar', [ComandoController::class, 'cerrarSesion'])->name('sesiones.cerrar');
        Route::put('/sesiones/{sesion}/asistencia', [ComandoController::class, 'asistencia'])->name('asistencia');
        Route::put('/sesiones/{sesion}/bancas/{banca}', [ComandoController::class, 'banca'])->name('bancas.update');
        Route::put('/sesiones/{sesion}/documentos/{documento}', [ComandoController::class, 'documento'])->name('documentos.mostrar');
        Route::post('/votaciones', [ComandoController::class, 'crearVotacion'])->name('votaciones.store');
        Route::post('/votaciones/{votacion}/iniciar', [ComandoController::class, 'iniciarVotacion'])->name('votaciones.iniciar');
        Route::post('/votaciones/{votacion}/cerrar', [ComandoController::class, 'cerrarVotacion'])->name('votaciones.cerrar');
        Route::put('/votaciones/{votacion}/voto', [ComandoController::class, 'votoAsistido'])->name('votaciones.voto');
    });

    Route::get('/bancas/{banca}', [BancaController::class, 'show'])->name('bancas.show');
    Route::get('/bancas/{banca}/estado', [BancaController::class, 'estado'])->name('bancas.estado');
    Route::get('/bancas/{banca}/documentos/{documento}', [BancaController::class, 'documento'])->name('bancas.documento');
    Route::put('/bancas/{banca}/votaciones/{votacion}/voto', [BancaController::class, 'votar'])->name('bancas.votar');
});
