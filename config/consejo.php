<?php

/*
| Datos del consejo profesional (ficticio) y reglas de negocio configurables.
| Todo es de ejemplo: el consejo no existe.
*/

return [
    'nombre' => env('CONSEJO_NOMBRE', 'Consejo Profesional de Ingeniería y Arquitectura de Villa Ficticia'),
    'sigla' => env('CONSEJO_SIGLA', 'CPIA-VF'),

    // Importe mensual de la cuota de matrícula y día del mes en que vence.
    'cuota' => [
        'importe' => (float) env('CONSEJO_CUOTA_IMPORTE', 18500),
        'dia_vencimiento' => (int) env('CONSEJO_CUOTA_DIA_VENCIMIENTO', 10),
    ],

    // Días de validez de un certificado de matrícula vigente.
    'certificado' => [
        'dias_validez' => (int) env('CONSEJO_CERTIFICADO_DIAS_VALIDEZ', 90),
    ],

    // Límite de adjuntos por trámite (tamaño en KB).
    'adjuntos' => [
        'max_archivos' => 5,
        'max_kb' => 5120,
    ],
];
