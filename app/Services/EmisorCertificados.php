<?php

namespace App\Services;

use App\Exceptions\OperacionNoPermitida;
use App\Models\Certificado;
use App\Models\Profesional;
use App\Models\Tramite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/** Emite certificados de matrícula vigente y arma su PDF. */
class EmisorCertificados
{
    /**
     * Revisa que el profesional esté en condiciones de recibir el certificado.
     *
     * @throws OperacionNoPermitida
     */
    public function verificarRequisitos(Profesional $profesional): void
    {
        if (! $profesional->estaActiva()) {
            throw new OperacionNoPermitida(
                "No se puede emitir el certificado: la matrícula está {$profesional->estado->etiqueta()}."
            );
        }

        $vencidas = $profesional->cuotas()->vencidas()->count();
        if ($vencidas > 0) {
            throw new OperacionNoPermitida(
                "No se puede emitir el certificado: el profesional tiene {$vencidas} cuota(s) vencida(s) sin pagar."
            );
        }
    }

    public function emitir(Profesional $profesional, ?Tramite $tramite = null): Certificado
    {
        $this->verificarRequisitos($profesional);

        return Certificado::create([
            'codigo' => Certificado::generarCodigo(),
            'profesional_id' => $profesional->id,
            'tramite_id' => $tramite?->id,
            'emitido_en' => now(),
            'valido_hasta' => today()->addDays(config('consejo.certificado.dias_validez')),
        ]);
    }

    public function descargarPdf(Certificado $certificado): Response
    {
        $certificado->loadMissing('profesional');

        return Pdf::loadView('pdf.certificado', [
            'certificado' => $certificado,
            'profesional' => $certificado->profesional,
        ])
            ->setPaper('a4')
            ->download("certificado-{$certificado->codigo}.pdf");
    }
}
