<?php

use App\Http\Controllers\Api\CategoriaApiController;
use Illuminate\Support\Facades\Route;

// GET /api/categorias  (también responde con barra final: /api/categorias/)
Route::get('/categorias', [CategoriaApiController::class, 'index'])->name('api.categorias');
