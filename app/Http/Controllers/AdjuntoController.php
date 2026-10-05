<?php

namespace App\Http\Controllers;

use App\Models\TramiteAdjunto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjuntoController extends Controller
{
    /** Los PDF están en el disco privado: solo se bajan pasando por la policy. */
    public function descargar(TramiteAdjunto $adjunto): StreamedResponse
    {
        $this->authorize('descargar', $adjunto);

        abort_unless(Storage::disk('local')->exists($adjunto->ruta), 404, 'El archivo ya no está disponible.');

        return Storage::disk('local')->download($adjunto->ruta, $adjunto->nombre_original);
    }
}
