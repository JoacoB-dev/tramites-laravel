<?php

use App\Http\Controllers\AdjuntoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\Panel;
use App\Http\Controllers\Portal;
use App\Http\Controllers\VerificacionController;
use Illuminate\Support\Facades\Route;

// --- Público -------------------------------------------------------------
Route::view('/', 'inicio')->name('inicio');

Route::get('/verificar', [VerificacionController::class, 'buscar'])->name('verificar.buscar');
Route::get('/verificar/{codigo}', [VerificacionController::class, 'show'])
    ->where('codigo', '[A-Za-z0-9-]{1,20}')
    ->middleware('throttle:30,1')
    ->name('verificar.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// --- Autenticado ---------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Compartidas entre portal y panel (la policy decide quién puede)
    Route::get('/adjuntos/{adjunto}', [AdjuntoController::class, 'descargar'])->name('adjuntos.descargar');
    Route::get('/certificados/{certificado}/pdf', [CertificadoController::class, 'descargar'])->name('certificados.descargar');

    // Portal del profesional
    Route::prefix('portal')->name('portal.')->middleware('can:acceder-portal')->group(function () {
        Route::get('/', Portal\InicioController::class)->name('inicio');
        Route::get('/tramites', [Portal\TramiteController::class, 'index'])->name('tramites.index');
        Route::get('/tramites/nuevo', [Portal\TramiteController::class, 'create'])->name('tramites.create');
        Route::post('/tramites', [Portal\TramiteController::class, 'store'])->name('tramites.store');
        Route::get('/tramites/{tramite}', [Portal\TramiteController::class, 'show'])->name('tramites.show');
        Route::post('/tramites/{tramite}/respuesta', [Portal\TramiteController::class, 'responder'])->name('tramites.responder');
        Route::get('/cuotas', Portal\CuotaController::class)->name('cuotas');
    });

    // Panel interno (empleados y administradores)
    Route::prefix('panel')->name('panel.')->middleware('can:acceder-panel')->group(function () {
        Route::get('/', Panel\InicioController::class)->name('inicio');

        Route::get('/tramites', [Panel\TramiteController::class, 'index'])->name('tramites.index');
        Route::get('/tramites/{tramite}', [Panel\TramiteController::class, 'show'])->name('tramites.show');
        Route::patch('/tramites/{tramite}/estado', [Panel\TramiteController::class, 'cambiarEstado'])->name('tramites.estado');

        Route::resource('profesionales', Panel\ProfesionalController::class)
            ->parameters(['profesionales' => 'profesional'])
            ->except('destroy');
        Route::patch('/profesionales/{profesional}/estado', [Panel\ProfesionalController::class, 'cambiarEstado'])
            ->name('profesionales.estado');

        Route::get('/cuotas', [Panel\CuotaController::class, 'index'])->name('cuotas.index');
        Route::post('/cuotas/{cuota}/pago', [Panel\CuotaController::class, 'registrarPago'])->name('cuotas.pago');

        Route::post('/certificados/{certificado}/anulacion', [CertificadoController::class, 'anular'])->name('certificados.anular');
    });
});
