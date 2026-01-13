<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoriaController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\MesaController;
use App\Http\Controllers\API\OrdenController;
use App\Http\Controllers\API\ComprobanteController;
use App\Http\Controllers\API\CajaController;



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

    // Categorías(Solo Administrador puede crear, actualizar y eliminar)
    Route::prefix('categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::get('con-productos', [CategoriaController::class, 'conProductos']);
        Route::get('{id}', [CategoriaController::class, 'show']);
        // Solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [CategoriaController::class, 'store']);
            Route::put('{id}', [CategoriaController::class, 'update']);
            Route::delete('{id}', [CategoriaController::class, 'destroy']);
        });
    });

    // Productos(Administrador y Cajero pueden crear, actualizar y eliminar)
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::get('stock-bajo', [ProductoController::class, 'stockBajo']);
        Route::get('{id}', [ProductoController::class, 'show']);
        // Admin y Cajero
        Route::middleware('role:Administrador,Cajero')->group(function () {
            Route::post('/', [ProductoController::class, 'store']);
            Route::put('{id}', [ProductoController::class, 'update']);
            Route::delete('{id}', [ProductoController::class, 'destroy']);
            Route::patch('{id}/stock', [ProductoController::class, 'actualizarStock']);
        });
    });

    // Mesas(Administrador y Mozo pueden gestionar mesas)
    Route::prefix('mesas')->group(function () {
        Route::get('/', [MesaController::class, 'index']);
        Route::get('libres', [MesaController::class, 'libres']);
        Route::get('ocupadas', [MesaController::class, 'ocupadas']);
        Route::get('resumen', [MesaController::class, 'resumen']);
        Route::get('{id}', [MesaController::class, 'show']);
        Route::patch('{id}/estado', [MesaController::class, 'cambiarEstado']);
        // Solo Admin puede crear/editar/eliminar mesas
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [MesaController::class, 'store']);
            Route::put('{id}', [MesaController::class, 'update']);
            Route::delete('{id}', [MesaController::class, 'destroy']);
        });
       
    });

    // Órdenes(Mesero y Cajero pueden gestionar órdenes)
    Route::prefix('ordenes')->group(function () {
        Route::get('/', [OrdenController::class, 'index']);
        Route::get('activas', [OrdenController::class, 'activas']);
        Route::get('cocina', [OrdenController::class, 'cocina']);
        Route::get('{id}', [OrdenController::class, 'show']);
        // Mesero y Cajero pueden crear y modificar
        Route::middleware('role:Administrador,Mesero,Cajero')->group(function () {
            Route::post('/', [OrdenController::class, 'store']);
            Route::post('{id}/productos', [OrdenController::class, 'agregarProductos']);
            Route::patch('{id}/estado', [OrdenController::class, 'cambiarEstado']);
            Route::post('{id}/cancelar', [OrdenController::class, 'cancelar']);
        });
    });

    // Comprobantes(Administrador y Cajero pueden gestionar comprobantes)
    Route::prefix('comprobantes')->group(function () {
        Route::middleware('role:Administrador,Cajero')->group(function () {
            Route::get('/', [ComprobanteController::class, 'index']);
            Route::get('resumen-dia', [ComprobanteController::class, 'resumenDia']);
            Route::get('{id}', [ComprobanteController::class, 'show']);
            Route::post('generar', [ComprobanteController::class, 'generar']);
            Route::post('{id}/anular', [ComprobanteController::class, 'anular']);
        });
    });

    // Caja(Solo Administrador y Cajero pueden gestionar la caja)
    Route::prefix('caja')->group(function () {
        Route::middleware('role:Administrador,Cajero')->group(function () {
            Route::get('actual', [CajaController::class, 'actual']);
            Route::get('historial', [CajaController::class, 'historial']);
            Route::get('{id}', [CajaController::class, 'show']);
            Route::post('abrir', [CajaController::class, 'abrir']);
            Route::post('{id}/cerrar', [CajaController::class, 'cerrar']);
        });
    });
});