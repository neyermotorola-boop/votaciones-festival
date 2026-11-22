<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\PublicoController;
use App\Http\Middleware\EsAdmin;
use App\Http\Middleware\PermisoAdmin;
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
    Route::get('/olvide-contrasena', [AuthController::class, 'olvideForm'])->name('password.request');
    Route::post('/olvide-contrasena', [AuthController::class, 'olvide'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [AuthController::class, 'restablecerForm'])->name('password.reset');
    Route::post('/restablecer-contrasena', [AuthController::class, 'restablecer'])->middleware('throttle:10,1')->name('password.update');
    // Ruta anterior de recuperación (citada en la documentación): lleva al flujo con enlace seguro.
    Route::redirect('/recuperar', '/olvide-contrasena')->name('recuperar');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::post('/votaciones/{votacion}/confirmar', [PublicoController::class, 'confirmar'])->name('votacion.confirmar');
    Route::post('/votaciones/{votacion}/votar', [PublicoController::class, 'votar'])->middleware('throttle:30,1')->name('votacion.votar');
    Route::get('/mis-votos', [PublicoController::class, 'misVotos'])->name('mis-votos');
    Route::get('/mi-documento', [DocumentoController::class, 'form'])->name('documento');
    Route::post('/mi-documento', [DocumentoController::class, 'guardar'])->name('documento.guardar');
    Route::get('/votaciones/{votacion}/gracias', [PublicoController::class, 'gracias'])->name('votacion.gracias');
});

// Panel de administración: cada sección exige su permiso.
// El administrador tiene todos; el gestor, solo los que el administrador le asignó.
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\PanelController::class, 'index'])->middleware(PermisoAdmin::class.':panel')->name('panel');

    Route::middleware(PermisoAdmin::class.':resultados')->group(function () {
        Route::get('votos', [Admin\VotoController::class, 'index'])->name('votos');
        Route::get('graficos', [Admin\GraficosController::class, 'index'])->name('graficos');
    });

    Route::middleware(PermisoAdmin::class.':reportes')->group(function () {
        Route::get('reportes', [Admin\ReporteController::class, 'index'])->name('reportes');
        Route::get('reportes/excel', [Admin\ReporteController::class, 'exportar'])->name('reportes.excel');
    });

    Route::middleware(PermisoAdmin::class.':ediciones')->group(function () {
        Route::resource('ediciones', Admin\EdicionController::class)->parameters(['ediciones' => 'edicion'])->except('show');
    });

    Route::middleware(PermisoAdmin::class.':categorias')->group(function () {
        Route::resource('categorias', Admin\CategoriaController::class)->except('show');
    });

    Route::middleware(PermisoAdmin::class.':votaciones')->group(function () {
        Route::resource('votaciones', Admin\VotacionController::class)->parameters(['votaciones' => 'votacion']);
        Route::post('votaciones/{votacion}/estado', [Admin\VotacionController::class, 'estado'])->name('votaciones.estado');
        Route::post('votaciones/{votacion}/publicar', [Admin\VotacionController::class, 'publicar'])->name('votaciones.publicar');
        Route::post('votaciones/{votacion}/opciones', [Admin\VotacionController::class, 'opcionStore'])->name('opciones.store');
        Route::put('votaciones/{votacion}/opciones/{opcion}', [Admin\VotacionController::class, 'opcionUpdate'])->name('opciones.update');
        Route::delete('votaciones/{votacion}/opciones/{opcion}', [Admin\VotacionController::class, 'opcionDestroy'])->name('opciones.destroy');
    });

    // Exportación a Excel de cada tabla: cada una exige el mismo permiso que su sección.
    Route::prefix('exportar')->name('exportar.')->controller(Admin\ExportarController::class)->group(function () {
        Route::get('ediciones', 'ediciones')->middleware(PermisoAdmin::class.':ediciones')->name('ediciones');
        Route::get('categorias', 'categorias')->middleware(PermisoAdmin::class.':categorias')->name('categorias');
        Route::get('votaciones', 'votaciones')->middleware(PermisoAdmin::class.':votaciones')->name('votaciones');
        Route::get('votaciones/{votacion}/opciones', 'opciones')->middleware(PermisoAdmin::class.':votaciones')->name('opciones');
        Route::get('votos', 'votos')->middleware(PermisoAdmin::class.':resultados')->name('votos');
        // Datos personales y bitácora: solo el administrador.
        Route::get('votantes', 'votantes')->middleware(EsAdmin::class)->name('votantes');
        Route::get('bitacora', 'bitacora')->middleware(EsAdmin::class)->name('bitacora');
    });

    // Usuarios y permisos: solo el administrador.
    Route::middleware(EsAdmin::class)->group(function () {
        Route::resource('usuarios', Admin\UsuarioController::class)->parameters(['usuarios' => 'usuario'])->except(['show', 'destroy']);
        Route::post('usuarios/{usuario}/estado', [Admin\UsuarioController::class, 'estado'])->name('usuarios.estado');
    });
});
