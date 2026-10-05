<?php

namespace Database\Seeders;

use App\Enums\EstadoTramite;
use App\Enums\Rol;
use App\Models\Certificado;
use App\Models\Cuota;
use App\Models\Profesional;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Cuit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Usuarios demo (contraseña demo1234) + ~40 profesionales con cuotas,
 * trámites en distintos estados con su historial y algunos certificados.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'demo1234';

    /** @var Collection<int, User> */
    private Collection $empleados;

    private string $pdfEjemplo;

    public function run(): void
    {
        fake()->seed(2026); // datos reproducibles

        $admin = User::create(['name' => 'Ana Administradora', 'email' => 'admin@demo.local', 'password' => self::PASSWORD, 'rol' => Rol::Admin]);
        $empleado = User::create(['name' => 'Esteban Empleado', 'email' => 'empleado@demo.local', 'password' => self::PASSWORD, 'rol' => Rol::Empleado]);
        $empleada2 = User::create(['name' => 'Lucía Mesa de Entradas', 'email' => 'lucia@demo.local', 'password' => self::PASSWORD, 'rol' => Rol::Empleado]);
        $this->empleados = collect([$empleado, $empleada2, $admin]);

        // PDF de ejemplo para los adjuntos (se genera, no se versiona un binario)
        $this->pdfEjemplo = Pdf::loadHTML('<h1>Plano de ejemplo</h1><p>Archivo generado por el seeder. Datos ficticios.</p>')->output();

        $tipos = TipoTramite::all()->keyBy('codigo');

        $demo = $this->crearProfesionalDemo();
        $this->crearTramitesDemo($demo, $tipos, $empleado);

        // Profesionales al azar: la mayoría activos, algunos suspendidos o de baja
        $otros = Profesional::factory()->count(34)->create()
            ->merge(Profesional::factory()->count(4)->suspendida()->create())
            ->merge(Profesional::factory()->count(2)->baja()->create());

        // Un par con usuario para el portal (además del demo)
        $otros->take(5)->each(function (Profesional $p) {
            $usuario = User::create([
                'name' => "{$p->nombre} {$p->apellido}",
                'email' => $p->email,
                'password' => self::PASSWORD,
                'rol' => Rol::Profesional,
            ]);
            $p->update(['user_id' => $usuario->id]);
        });

        foreach ($otros as $profesional) {
            $this->crearCuotas($profesional, probabilidadPago: $profesional->estaActiva() ? 85 : 30);

            if ($profesional->estaActiva()) {
                $cantidad = fake()->numberBetween(0, 3);
                for ($i = 0; $i < $cantidad; $i++) {
                    $this->crearTramiteAlAzar($profesional, fake()->randomElement($tipos->all()));
                }
            }
        }
    }

    private function crearProfesionalDemo(): Profesional
    {
        $usuario = User::create([
            'name' => 'Martina Gómez',
            'email' => 'profesional@demo.local',
            'password' => self::PASSWORD,
            'rol' => Rol::Profesional,
        ]);

        $profesional = Profesional::create([
            'user_id' => $usuario->id,
            'matricula' => 'AR-10234',
            'apellido' => 'Gómez',
            'nombre' => 'Martina',
            'cuit' => Cuit::desdeDni(30123456, '27'),
            'email' => 'profesional@demo.local',
            'telefono' => '11 5555-0101',
            'domicilio' => 'Av. Siempreviva 742',
            'localidad' => 'Villa Ficticia',
            'profesion' => 'Arquitecto/a',
            'fecha_matriculacion' => '2015-03-02',
            'estado' => 'activa',
        ]);

        // Todas pagas menos la del mes actual (pendiente): está al día
        $this->crearCuotas($profesional, probabilidadPago: 100);

        return $profesional;
    }

    /** @param Collection<string, TipoTramite> $tipos */
    private function crearTramitesDemo(Profesional $demo, Collection $tipos, User $empleado): void
    {
        // 1) Certificado aprobado, con código fijo para probar /verificar
        $cert = $this->crearTramiteConHistorial($demo, $tipos[TipoTramite::CERTIFICADO_VIGENCIA],
            [EstadoTramite::EnRevision, EstadoTramite::Aprobado], $empleado, now()->subDays(20));
        Certificado::create([
            'codigo' => 'DEMO-CERT-2026',
            'profesional_id' => $demo->id,
            'tramite_id' => $cert->id,
            'emitido_en' => $cert->resuelto_en,
            'valido_hasta' => $cert->resuelto_en->copy()->addDays(config('consejo.certificado.dias_validez'))->toDateString(),
        ]);

        // 2) Visado de planos observado: el profesional tiene que responder
        $this->crearTramiteConHistorial($demo, $tipos[TipoTramite::VISADO_PLANOS],
            [EstadoTramite::EnRevision, EstadoTramite::Observado], $empleado, now()->subDays(6),
            observacionFinal: 'Falta la firma en la lámina 3 y la memoria técnica. Por favor adjuntá la versión corregida.');

        // 3) Cambio de domicilio recién iniciado
        $this->crearTramiteConHistorial($demo, $tipos[TipoTramite::CAMBIO_DOMICILIO], [], null, now()->subDay(),
            datos: ['domicilio' => 'Calle Falsa 123', 'localidad' => 'San Ejemplo']);
    }

    private function crearTramiteAlAzar(Profesional $profesional, TipoTramite $tipo): void
    {
        // Caminos posibles por el flujo de estados
        $camino = fake()->randomElement([
            [],
            [EstadoTramite::EnRevision],
            [EstadoTramite::EnRevision, EstadoTramite::Observado],
            [EstadoTramite::EnRevision, EstadoTramite::Aprobado],
            [EstadoTramite::EnRevision, EstadoTramite::Aprobado],
            [EstadoTramite::EnRevision, EstadoTramite::Observado, EstadoTramite::EnRevision, EstadoTramite::Aprobado],
            [EstadoTramite::EnRevision, EstadoTramite::Rechazado],
        ]);

        $datos = $tipo->esCambioDomicilio()
            ? ['domicilio' => fake()->streetAddress(), 'localidad' => 'Villa Ficticia']
            : [];

        $inicio = CarbonImmutable::instance(fake()->dateTimeBetween('-75 days', '-1 day'));

        $tramite = $this->crearTramiteConHistorial($profesional, $tipo, $camino, fake()->randomElement($this->empleados->all()), $inicio, datos: $datos);

        if ($tipo->esCertificado() && $tramite->estado === EstadoTramite::Aprobado) {
            Certificado::create([
                'codigo' => Certificado::generarCodigo(),
                'profesional_id' => $profesional->id,
                'tramite_id' => $tramite->id,
                'emitido_en' => $tramite->resuelto_en,
                'valido_hasta' => $tramite->resuelto_en->copy()->addDays(90)->toDateString(),
            ]);
        }
    }

    /**
     * Crea el trámite y recorre los estados dejando cada movimiento en el historial,
     * con fechas crecientes para que la línea de tiempo tenga sentido.
     *
     * @param  list<EstadoTramite>  $camino  estados posteriores a Iniciado
     */
    private function crearTramiteConHistorial(
        Profesional $profesional,
        TipoTramite $tipo,
        array $camino,
        ?User $empleado,
        \DateTimeInterface $inicio,
        ?string $observacionFinal = null,
        array $datos = [],
    ): Tramite {
        $fecha = CarbonImmutable::instance($inicio);

        $tramite = Tramite::create([
            'profesional_id' => $profesional->id,
            'tipo_tramite_id' => $tipo->id,
            'estado' => EstadoTramite::Iniciado,
            'descripcion' => $tipo->requiere_adjunto ? 'Obra: vivienda unifamiliar, '.fake()->numberBetween(80, 260).' m².' : null,
            'datos' => $datos ?: null,
        ]);
        $tramite->forceFill(['numero' => Tramite::generarNumero($tramite->id, $fecha->year), 'created_at' => $fecha, 'updated_at' => $fecha])->save();

        $tramite->movimientos()->forceCreate([
            'user_id' => $profesional->user_id,
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTramite::Iniciado,
            'observacion' => 'Trámite iniciado desde el portal.',
            'created_at' => $fecha,
        ]);

        if ($tipo->requiere_adjunto) {
            $ruta = "tramites/{$tramite->id}/plano-".fake()->numberBetween(100, 999).'.pdf';
            Storage::disk('local')->put($ruta, $this->pdfEjemplo);
            $tramite->adjuntos()->create([
                'user_id' => $profesional->user_id,
                'nombre_original' => 'planos-obra.pdf',
                'ruta' => $ruta,
                'tamanio' => strlen($this->pdfEjemplo),
            ]);
        }

        $anterior = EstadoTramite::Iniciado;
        foreach ($camino as $i => $estado) {
            $fecha = $fecha->addHours(fake()->numberBetween(4, 72));
            $esUltimo = $i === array_key_last($camino);

            $observacion = match ($estado) {
                EstadoTramite::Observado => $esUltimo && $observacionFinal ? $observacionFinal : 'Falta documentación: adjuntar la versión firmada.',
                EstadoTramite::Rechazado => 'El trámite no corresponde a la incumbencia del título.',
                EstadoTramite::EnRevision => $anterior === EstadoTramite::Observado ? 'Adjunto la documentación corregida.' : null,
                EstadoTramite::Aprobado => 'Aprobado.',
                default => null,
            };

            // La respuesta a una observación la hace el profesional; el resto, el consejo
            $autor = $anterior === EstadoTramite::Observado ? $profesional->user_id : $empleado?->id;

            $tramite->movimientos()->forceCreate([
                'user_id' => $autor,
                'estado_anterior' => $anterior,
                'estado_nuevo' => $estado,
                'observacion' => $observacion,
                'created_at' => $fecha,
            ]);
            $anterior = $estado;
        }

        if ($camino !== []) {
            $final = end($camino);
            $tramite->forceFill([
                'estado' => $final,
                'empleado_id' => $empleado?->id,
                'resuelto_en' => $final->esFinal() ? $fecha : null,
                'updated_at' => $fecha,
            ])->save();

            if ($final === EstadoTramite::Aprobado && $tipo->esCambioDomicilio() && $datos) {
                $profesional->update($datos);
            }
        }

        return $tramite;
    }

    /** Cuotas de los últimos 6 meses (incluido el actual). */
    private function crearCuotas(Profesional $profesional, int $probabilidadPago): void
    {
        $mesActual = CarbonImmutable::now()->startOfMonth();

        for ($i = 5; $i >= 0; $i--) {
            $mes = $mesActual->subMonths($i);
            $vencimiento = $mes->setDay(config('consejo.cuota.dia_vencimiento'));
            // La cuota del mes actual solo puede estar paga si ya pasó el vencimiento
            $pagada = fake()->boolean($probabilidadPago) && ($i > 0 || $vencimiento->isPast());

            Cuota::create([
                'profesional_id' => $profesional->id,
                'periodo' => $mes->format('Y-m'),
                'importe' => config('consejo.cuota.importe'),
                'vencimiento' => $vencimiento,
                'pagada_en' => $pagada ? $vencimiento->subDays(fake()->numberBetween(0, 8)) : null,
                'medio_pago' => $pagada ? fake()->randomElement(array_keys(Cuota::MEDIOS_PAGO)) : null,
                'registrada_por' => $pagada ? fake()->randomElement($this->empleados->all())->id : null,
            ]);
        }
    }
}
