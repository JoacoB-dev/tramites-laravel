<?php

namespace App\Http\Requests;

use App\Models\TipoTramite;
use App\Models\Tramite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IniciarTramiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tramite::class);
    }

    public function rules(): array
    {
        $tipo = $this->tipoElegido();
        $maxArchivos = config('consejo.adjuntos.max_archivos');

        return [
            'tipo_tramite_id' => ['required', 'integer', Rule::exists('tipos_tramite', 'id')->where('activo', true)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'adjuntos' => [$tipo?->requiere_adjunto ? 'required' : 'nullable', 'array', "max:{$maxArchivos}"],
            'adjuntos.*' => ['file', 'mimes:pdf', 'max:'.config('consejo.adjuntos.max_kb')],
            // Solo para cambio de domicilio
            'domicilio' => [Rule::requiredIf($tipo?->esCambioDomicilio() ?? false), 'nullable', 'string', 'max:150'],
            'localidad' => [Rule::requiredIf($tipo?->esCambioDomicilio() ?? false), 'nullable', 'string', 'max:80'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_tramite_id' => 'tipo de trámite',
            'adjuntos' => 'adjuntos',
            'adjuntos.*' => 'adjunto',
            'domicilio' => 'nuevo domicilio',
            'localidad' => 'nueva localidad',
        ];
    }

    public function messages(): array
    {
        return [
            'adjuntos.required' => 'Este tipo de trámite requiere al menos un PDF adjunto.',
            'adjuntos.*.mimes' => 'Los adjuntos tienen que ser archivos PDF.',
        ];
    }

    public function tipoElegido(): ?TipoTramite
    {
        $id = $this->input('tipo_tramite_id');

        return is_numeric($id) ? TipoTramite::find((int) $id) : null;
    }

    /** Datos propios del tipo que se guardan en la columna JSON "datos". */
    public function datosDelTipo(): array
    {
        return $this->tipoElegido()?->esCambioDomicilio()
            ? $this->safe()->only(['domicilio', 'localidad'])
            : [];
    }
}
