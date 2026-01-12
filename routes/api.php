<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;


///RUTAS PÚBLICAS (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']); // Opcional
});

///RUTAS PROTEGIDAS (requieren autenticación)
Route::middleware('auth:api')->group(function () {
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});