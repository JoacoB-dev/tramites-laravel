<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profesional = $request->user()->profesional;

        return view('portal.inicio', [
            'profesional' => $profesional,
            'tramites' => $profesional->tramites()->with('tipo')->latest()->limit(5)->get(),
            'cuotasImpagas' => $profesional->cuotas()->impagas()->orderBy('vencimiento')->get(),
            'certificados' => $profesional->certificados()->latest('emitido_en')->limit(3)->get(),
        ]);
    }
}
