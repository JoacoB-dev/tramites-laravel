<?php

namespace Tests\Feature;

use App\Enums\EstadoTramite;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Notifications\EstadoTramiteActualizado;
use App\Services\GestorTramites;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_notificacion_se_encola_y_el_mail_tiene_el_estado_y_la_observacion(): void
    {
        Queue::fake();
        $this->crearTiposDeTramite();
        $usuario = $this->profesional();
        $tramite = Tramite::factory()->enEstado(EstadoTramite::EnRevision)->create([
            'profesional_id' => $usuario->profesional->id,
            'tipo_tramite_id' => $this->tipo(TipoTramite::VISADO_PLANOS)->id,
        ]);

        app(GestorTramites::class)->cambiarEstado($tramite, EstadoTramite::Observado, $this->empleado(), 'Falta la firma');

        // Se encoló (no se mandó en el momento)
        Queue::assertPushed(SendQueuedNotifications::class);
        $this->assertInstanceOf(ShouldQueue::class, new EstadoTramiteActualizado($tramite));

        $mail = (new EstadoTramiteActualizado($tramite->fresh(), 'Falta la firma'))->toMail($usuario);
        $this->assertSame("Trámite {$tramite->numero}: Observado", $mail->subject);
        $this->assertStringContainsString('Falta la firma', implode(' ', $mail->introLines));
        $this->assertSame(route('portal.tramites.show', $tramite), $mail->actionUrl);
    }
}
