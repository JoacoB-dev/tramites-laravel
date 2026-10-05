<?php

use App\Exceptions\OperacionNoPermitida;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Si alguien logueado entra a /login lo mando a su pantalla de inicio
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->rutaInicio());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Reglas de negocio que se escapan de un controller: 422 con el mensaje
        $exceptions->render(function (OperacionNoPermitida $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
