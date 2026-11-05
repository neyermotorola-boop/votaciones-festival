<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicoController;
use App\Http\Middleware\EsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicoController::class, 'home'])->name('home');
Route::get('/ediciones', [PublicoController::class, 'ediciones'])->name('ediciones');
Route::get('/ediciones/{edicion}', [PublicoController::class, 'edicion'])->name('edicion');
Route::get('/categorias/{categoria}', [PublicoController::class, 'categoria'])->name('categoria');
Route::get('/votaciones/{votacion}', [PublicoController::class, 'votacion'])->name('votacion');

Route::middleware('guest')->group(function () {
    Route::get('/registro', [AuthController::class, 'registroForm'])->name('registro');
    Route::post('/registro', [AuthController::class, 'registro'])->middleware('throttle:10,1');
    Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::post('/votaciones/{votacion}/confirmar', [PublicoController::class, 'confirmar'])->name('votacion.confirmar');
    Route::post('/votaciones/{votacion}/votar', [PublicoController::class, 'votar'])->middleware('throttle:30,1')->name('votacion.votar');
    Route::get('/votaciones/{votacion}/gracias', [PublicoController::class, 'gracias'])->name('votacion.gracias');
});

Route::middleware(['auth', EsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\PanelController::class, 'index'])->name('panel');
    Route::get('reportes', [Admin\ReporteController::class, 'index'])->name('reportes');
    Route::get('reportes/excel', [Admin\ReporteController::class, 'exportar'])->name('reportes.excel');
    Route::resource('ediciones', Admin\EdicionController::class)->parameters(['ediciones' => 'edicion'])->except('show');
    Route::resource('categorias', Admin\CategoriaController::class)->except('show');
    Route::resource('votaciones', Admin\VotacionController::class)->parameters(['votaciones' => 'votacion']);
    Route::post('votaciones/{votacion}/estado', [Admin\VotacionController::class, 'estado'])->name('votaciones.estado');
    Route::post('votaciones/{votacion}/publicar', [Admin\VotacionController::class, 'publicar'])->name('votaciones.publicar');
    Route::post('votaciones/{votacion}/opciones', [Admin\VotacionController::class, 'opcionStore'])->name('opciones.store');
    Route::delete('votaciones/{votacion}/opciones/{opcion}', [Admin\VotacionController::class, 'opcionDestroy'])->name('opciones.destroy');
});
