<?php

namespace App\Notifications;

use App\Enums\EstadoTramite;
use App\Models\Tramite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail al profesional cuando cambia el estado de su trámite.
 * Implementa ShouldQueue: se encola (driver database) y lo manda el worker.
 */
class EstadoTramiteActualizado extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly Tramite $tramite,
        public readonly ?string $observacion = null,
    ) {
        // Se encola recién cuando se confirma la transacción
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tramite = $this->tramite->loadMissing('tipo');
        $estado = $tramite->estado;

        $mail = (new MailMessage)
            ->subject("Trámite {$tramite->numero}: {$estado->etiqueta()}")
            ->greeting("Hola {$notifiable->name}:")
            ->line("Tu trámite **{$tramite->numero}** ({$tramite->tipo->nombre}) pasó a estado **{$estado->etiqueta()}**.");

        if ($this->observacion) {
            $mail->line('Observación del consejo:')->line("> {$this->observacion}");
        }

        if ($estado === EstadoTramite::Observado) {
            $mail->line('Revisá la observación y respondela desde el portal para que sigamos con el trámite.');
        }

        if ($estado === EstadoTramite::Aprobado && $tramite->tipo->esCertificado()) {
            $mail->line('Ya podés descargar tu certificado desde el portal.');
        }

        return $mail
            ->action('Ver trámite', route('portal.tramites.show', $tramite))
            ->salutation('Saludos, '.config('consejo.sigla'));
    }
}
