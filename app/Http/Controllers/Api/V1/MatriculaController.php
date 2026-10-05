<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatriculaResource;
use App\Models\Profesional;
use App\Support\Cuit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MatriculaController extends Controller
{
    /** GET /api/v1/matriculas/{matricula} */
    public function show(string $matricula): MatriculaResource
    {
        $profesional = Profesional::where('matricula', strtoupper($matricula))->firstOrFail();

        return new MatriculaResource($profesional);
    }

    /** GET /api/v1/matriculas?cuit=20-12345678-6 */
    public function buscarPorCuit(Request $request): MatriculaResource
    {
        $request->validate(['cuit' => ['required', 'string']]);

        $cuit = Cuit::normalizar($request->query('cuit'));
        if (! Cuit::esValido($cuit)) {
            throw ValidationException::withMessages(['cuit' => 'El CUIT no es válido.']);
        }

        return new MatriculaResource(Profesional::where('cuit', $cuit)->firstOrFail());
    }
}
