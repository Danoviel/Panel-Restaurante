<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoriaController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\MesaController;



Route::get('test', function() {
    return response()->json([
        'message' => 'API funcionando',
        'timestamp' => now()
    ]);
});

///RUTAS PÚBLICAS (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

///RUTAS PROTEGIDAS (con autenticación)
Route::middleware('auth:api')->group(function () {
    //Auth routes   
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);    
    });

    // Categorías
    Route::prefix('categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::get('con-productos', [CategoriaController::class, 'conProductos']);
        Route::get('{id}', [CategoriaController::class, 'show']);
        Route::post('/', [CategoriaController::class, 'store']);
        Route::put('{id}', [CategoriaController::class, 'update']);
        Route::delete('{id}', [CategoriaController::class, 'destroy']);
    });

    // Productos
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::get('stock-bajo', [ProductoController::class, 'stockBajo']);
        Route::get('{id}', [ProductoController::class, 'show']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::put('{id}', [ProductoController::class, 'update']);
        Route::delete('{id}', [ProductoController::class, 'destroy']);
        Route::patch('{id}/stock', [ProductoController::class, 'actualizarStock']);
    });

    // Mesas
    Route::prefix('mesas')->group(function () {
        Route::get('/', [MesaController::class, 'index']);
        Route::get('libres', [MesaController::class, 'libres']);
        Route::get('ocupadas', [MesaController::class, 'ocupadas']);
        Route::get('resumen', [MesaController::class, 'resumen']);
        Route::get('{id}', [MesaController::class, 'show']);
        Route::post('/', [MesaController::class, 'store']);
        Route::put('{id}', [MesaController::class, 'update']);
        Route::delete('{id}', [MesaController::class, 'destroy']);
        Route::patch('{id}/estado', [MesaController::class, 'cambiarEstado']);
    });
});