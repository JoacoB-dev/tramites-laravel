<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado {{ $certificado->codigo }}</title>
    <style>
        /* dompdf: DejaVu Sans trae acentos y ñ */
        body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #222; margin: 30px 40px; }
        .encabezado { border-bottom: 3px solid #1f3a5f; padding-bottom: 10px; margin-bottom: 30px; }
        .encabezado h1 { font-size: 16px; color: #1f3a5f; margin: 0; }
        .encabezado p { margin: 2px 0 0; color: #666; font-size: 10px; }
        h2 { text-align: center; font-size: 20px; letter-spacing: 2px; margin: 30px 0; }
        .cuerpo { font-size: 13px; line-height: 1.8; text-align: justify; }
        .datos { margin: 25px 0; border-collapse: collapse; width: 100%; }
        .datos td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        .datos td:first-child { width: 35%; color: #555; }
        .verificacion { margin-top: 60px; border: 1px dashed #1f3a5f; padding: 12px; font-size: 11px; }
        .codigo { font-family: "DejaVu Sans Mono", monospace; font-size: 16px; font-weight: bold; letter-spacing: 2px; }
        .pie { position: fixed; bottom: 10px; left: 40px; right: 40px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ config('consejo.nombre') }}</h1>
        <p>{{ config('consejo.sigla') }} · Documento generado por el sistema de matrículas y trámites</p>
    </div>

    <h2>CERTIFICADO DE MATRÍCULA VIGENTE</h2>

    <p class="cuerpo">
        Se certifica que <strong>{{ $profesional->nombre }} {{ $profesional->apellido }}</strong>,
        CUIT {{ $profesional->cuit_formateado }}, se encuentra inscripto/a en este consejo con la matrícula
        <strong>{{ $profesional->matricula }}</strong> en la profesión de {{ $profesional->profesion }},
        con la matrícula <strong>ACTIVA</strong> y sin deuda de cuotas vencidas a la fecha de emisión.
    </p>

    <table class="datos">
        <tr><td>Matrícula</td><td>{{ $profesional->matricula }}</td></tr>
        <tr><td>Fecha de matriculación</td><td>{{ $profesional->fecha_matriculacion->format('d/m/Y') }}</td></tr>
        <tr><td>Domicilio profesional</td><td>{{ $profesional->domicilio }}, {{ $profesional->localidad }}</td></tr>
        <tr><td>Fecha de emisión</td><td>{{ $certificado->emitido_en->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Válido hasta</td><td>{{ $certificado->valido_hasta->format('d/m/Y') }}</td></tr>
    </table>

    <div class="verificacion">
        Código de verificación: <span class="codigo">{{ $certificado->codigo }}</span><br>
        Verificá la autenticidad y vigencia de este certificado en:<br>
        <strong>{{ $certificado->urlVerificacion() }}</strong>
    </div>

    <div class="pie">Consejo ficticio — documento de ejemplo generado por un proyecto de portfolio. No tiene validez legal.</div>
</body>
</html>
