<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Devuelve un token personal de Sanctum (Bearer). */
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciales incorrectas.']);
        }

        $token = $usuario->createToken($datos['dispositivo'] ?? 'api', ['*'], now()->addDays(30));

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'vence' => $token->accessToken->expires_at?->toIso8601String(),
            'usuario' => ['nombre' => $usuario->name, 'rol' => $usuario->rol->value],
        ], 201);
    }

    /** Revoca el token con el que se hizo la llamada. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Token revocado.']);
    }
}
