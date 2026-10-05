<?php

namespace Database\Seeders;

use App\Models\TipoTramite;
use Illuminate\Database\Seeder;

class TipoTramiteSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => TipoTramite::VISADO_PLANOS,
                'nombre' => 'Visado de planos',
                'descripcion' => 'Revisión y visado de planos de obra. Adjuntá los planos y la memoria técnica en PDF.',
                'requiere_adjunto' => true,
                'arancel' => 25000,
                'dias_estimados' => 10,
            ],
            [
                'codigo' => TipoTramite::CERTIFICADO_VIGENCIA,
                'nombre' => 'Certificado de matrícula vigente',
                'descripcion' => 'Certificado con código de verificación pública. Requiere matrícula activa y no tener cuotas vencidas.',
                'requiere_adjunto' => false,
                'arancel' => 0,
                'dias_estimados' => 2,
            ],
            [
                'codigo' => TipoTramite::CAMBIO_DOMICILIO,
                'nombre' => 'Cambio de domicilio profesional',
                'descripcion' => 'Actualiza el domicilio profesional registrado. Podés adjuntar un comprobante (opcional).',
                'requiere_adjunto' => false,
                'arancel' => 0,
                'dias_estimados' => 3,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoTramite::updateOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
