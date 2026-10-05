<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Services\EmisorCertificados;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificadoController extends Controller
{
    public function descargar(Certificado $certificado, EmisorCertificados $emisor): Response
    {
        $this->authorize('descargar', $certificado);

        return $emisor->descargarPdf($certificado);
    }

    public function anular(Request $request, Certificado $certificado): RedirectResponse
    {
        $this->authorize('anular', $certificado);

        $datos = $request->validate(['motivo_anulacion' => ['required', 'string', 'max:255']]);

        $certificado->update([
            'anulado_en' => now(),
            'motivo_anulacion' => $datos['motivo_anulacion'],
        ]);

        return back()->with('ok', "Certificado {$certificado->codigo} anulado.");
    }
}
