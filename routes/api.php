<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
| API REST v1 (prefijo /api). Autenticación con tokens personales de Sanctum:
| POST /api/v1/login devuelve el token y el resto se llama con
| "Authorization: Bearer <token>". Documentación: docs/api.md
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [V1\AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', [V1\AuthController::class, 'logout'])->name('logout');

        Route::get('/matriculas', [V1\MatriculaController::class, 'buscarPorCuit'])->name('matriculas.cuit');
        Route::get('/matriculas/{matricula}', [V1\MatriculaController::class, 'show'])->name('matriculas.show');

        Route::get('/tramites', [V1\TramiteController::class, 'index'])->name('tramites.index');
        Route::get('/tramites/{numero}', [V1\TramiteController::class, 'show'])->name('tramites.show');
    });
});
