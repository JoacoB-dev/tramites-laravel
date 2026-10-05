<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Verificación pública de certificados (sin login). */
class VerificacionController extends Controller
{
    /** Formulario: redirige a /verificar/{codigo} para que la URL se pueda compartir. */
    public function buscar(Request $request): View|RedirectResponse
    {
        $codigo = Certificado::normalizarCodigo((string) $request->query('codigo', ''));

        if ($codigo !== '') {
            return redirect()->route('verificar.show', $codigo);
        }

        return view('verificar.buscar');
    }

    public function show(string $codigo): View
    {
        $codigo = Certificado::normalizarCodigo($codigo);
        $certificado = Certificado::with('profesional')->where('codigo', $codigo)->first();

        return view('verificar.show', [
            'codigo' => $codigo,
            'certificado' => $certificado,
        ]);
    }
}
