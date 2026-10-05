<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuotaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profesional = $request->user()->profesional;

        return view('portal.cuotas', [
            'profesional' => $profesional,
            'cuotas' => $profesional->cuotas()->orderByDesc('periodo')->paginate(12),
            'deudaVencida' => $profesional->cuotas()->vencidas()->sum('importe'),
        ]);
    }
}
