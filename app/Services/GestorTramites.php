<?php

namespace App\Services;

use App\Enums\EstadoTramite;
use App\Exceptions\OperacionNoPermitida;
use App\Models\Profesional;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Models\TramiteMovimiento;
use App\Models\User;
use App\Notifications\EstadoTramiteActualizado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reglas del flujo de trámites. Los controllers (web y API) solo validan
 * la entrada y delegan acá, así la lógica queda en un solo lugar y se testea directo.
 */
class GestorTramites
{
    public function __construct(private readonly EmisorCertificados $certificados) {}

    /**
     * Crea un trámite en estado Iniciado con su primer movimiento y adjuntos.
     *
     * @param  array<string, mixed>  $datos  datos propios del tipo (ej. nuevo domicilio)
     * @param  list<UploadedFile>  $archivos
     */
    public function iniciar(
        Profesional $profesional,
        TipoTramite $tipo,
        User $autor,
        ?string $descripcion = null,
        array $datos = [],
        array $archivos = [],
    ): Tramite {
        if (! $profesional->estaActiva()) {
            throw new OperacionNoPermitida('Con la matrícula '.mb_strtolower($profesional->estado->etiqueta()).' no se pueden iniciar trámites.');
        }

        if ($tipo->requiere_adjunto && $archivos === []) {
            throw new OperacionNoPermitida("El trámite \"{$tipo->nombre}\" requiere al menos un PDF adjunto.");
        }

        $guardados = [];

        try {
            return DB::transaction(function () use ($profesional, $tipo, $autor, $descripcion, $datos, $archivos, &$guardados) {
                $tramite = Tramite::create([
                    'profesional_id' => $profesional->id,
                    'tipo_tramite_id' => $tipo->id,
                    'estado' => EstadoTramite::Iniciado,
                    'descripcion' => $descripcion,
                    'datos' => $datos ?: null,
                ]);

                // El número sale del id, por eso se asigna después de insertar
                $tramite->update(['numero' => Tramite::generarNumero($tramite->id)]);

                $this->registrarMovimiento($tramite, $autor, null, EstadoTramite::Iniciado, 'Trámite iniciado desde el portal.');
                $guardados = $this->guardarAdjuntos($tramite, $autor, $archivos);

                return $tramite;
            });
        } catch (Throwable $e) {
            // Si falló la base, no dejamos archivos huérfanos en el disco
            Storage::disk('local')->delete($guardados);
            throw $e;
        }
    }

    /**
     * Cambia el estado validando la transición y las reglas de negocio.
     * Deja el movimiento en el historial y avisa por mail (en cola) al profesional.
     *
     * @throws OperacionNoPermitida
     */
    public function cambiarEstado(Tramite $tramite, EstadoTramite $nuevo, User $autor, ?string $observacion = null): Tramite
    {
        $observacion = trim((string) $observacion) ?: null;

        $tramite = DB::transaction(function () use ($tramite, $nuevo, $autor, $observacion) {
            // Bloqueo la fila para que dos empleados no resuelvan el mismo trámite a la vez
            /** @var Tramite $actual */
            $actual = Tramite::whereKey($tramite->id)->lockForUpdate()->firstOrFail();
            $anterior = $actual->estado;

            if (! $anterior->puedePasarA($nuevo)) {
                throw new OperacionNoPermitida(
                    "No se puede pasar de \"{$anterior->etiqueta()}\" a \"{$nuevo->etiqueta()}\"."
                );
            }

            if ($nuevo->requiereObservacion() && $observacion === null) {
                throw new OperacionNoPermitida("Para marcar el trámite como \"{$nuevo->etiqueta()}\" tenés que escribir una observación.");
            }

            if ($nuevo === EstadoTramite::Aprobado) {
                $this->aplicarEfectosDeAprobacion($actual);
            }

            $actual->estado = $nuevo;
            $actual->resuelto_en = $nuevo->esFinal() ? now() : null;
            if ($actual->empleado_id === null && $autor->esStaff()) {
                $actual->empleado_id = $autor->id;
            }
            $actual->save();

            $this->registrarMovimiento($actual, $autor, $anterior, $nuevo, $observacion);

            return $actual;
        });

        $this->notificarProfesional($tramite, $autor, $observacion);

        return $tramite;
    }

    /**
     * El profesional contesta una observación: suma comentario y adjuntos,
     * y el trámite vuelve a "En revisión".
     *
     * @param  list<UploadedFile>  $archivos
     */
    public function responderObservacion(Tramite $tramite, User $autor, string $comentario, array $archivos = []): Tramite
    {
        if ($tramite->estado !== EstadoTramite::Observado) {
            throw new OperacionNoPermitida('Solo se puede responder un trámite que está observado.');
        }

        $guardados = $this->guardarAdjuntos($tramite, $autor, $archivos);

        try {
            return $this->cambiarEstado($tramite, EstadoTramite::EnRevision, $autor, $comentario);
        } catch (Throwable $e) {
            $tramite->adjuntos()->whereIn('ruta', $guardados)->delete();
            Storage::disk('local')->delete($guardados);
            throw $e;
        }
    }

    /**
     * Efectos al aprobar según el tipo de trámite. Corre dentro de la transacción
     * de cambiarEstado: si algo falla, el trámite no queda aprobado.
     */
    private function aplicarEfectosDeAprobacion(Tramite $tramite): void
    {
        $tramite->loadMissing('tipo', 'profesional');

        if ($tramite->tipo->esCertificado()) {
            $this->certificados->emitir($tramite->profesional, $tramite);
        }

        if ($tramite->tipo->esCambioDomicilio()) {
            $datos = $tramite->datos ?? [];
            if (empty($datos['domicilio']) || empty($datos['localidad'])) {
                throw new OperacionNoPermitida('El trámite no tiene cargado el nuevo domicilio.');
            }

            $tramite->profesional->update([
                'domicilio' => $datos['domicilio'],
                'localidad' => $datos['localidad'],
            ]);
        }
    }

    private function registrarMovimiento(Tramite $tramite, User $autor, ?EstadoTramite $anterior, EstadoTramite $nuevo, ?string $observacion): TramiteMovimiento
    {
        return $tramite->movimientos()->create([
            'user_id' => $autor->id,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $nuevo,
            'observacion' => $observacion,
        ]);
    }

    /**
     * Guarda los PDF en el disco privado y crea los registros.
     *
     * @param  list<UploadedFile>  $archivos
     * @return list<string> rutas guardadas (para poder borrarlas si algo falla)
     */
    private function guardarAdjuntos(Tramite $tramite, User $autor, array $archivos): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            $ruta = $archivo->store("tramites/{$tramite->id}", 'local');
            $rutas[] = $ruta;

            $tramite->adjuntos()->create([
                'user_id' => $autor->id,
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
                'ruta' => $ruta,
                'tamanio' => $archivo->getSize(),
            ]);
        }

        return $rutas;
    }

    /** No tiene sentido mandarle un mail al profesional por algo que hizo él mismo. */
    private function notificarProfesional(Tramite $tramite, User $autor, ?string $observacion): void
    {
        $destinatario = $tramite->profesional()->with('user')->first()?->user;

        if ($destinatario === null || $destinatario->is($autor)) {
            return;
        }

        $destinatario->notify(new EstadoTramiteActualizado($tramite, $observacion));
    }
}
