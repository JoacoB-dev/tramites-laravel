<?php

namespace Tests\Feature;

use App\Enums\EstadoTramite;
use App\Models\Certificado;
use App\Models\Cuota;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Notifications\EstadoTramiteActualizado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelTramitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->crearTiposDeTramite();
    }

    private function tramiteDe($usuario, string $tipo, EstadoTramite $estado, array $extra = []): Tramite
    {
        return Tramite::factory()->enEstado($estado)->create([
            'profesional_id' => $usuario->profesional->id,
            'tipo_tramite_id' => $this->tipo($tipo)->id,
        ] + $extra);
    }

    #[Test]
    public function lista_filtra_y_pagina(): void
    {
        $empleado = $this->empleado();
        $p1 = $this->profesional(['apellido' => 'Zabaleta']);
        $p2 = $this->profesional(['apellido' => 'Ibarra']);
        $this->tramiteDe($p1, TipoTramite::VISADO_PLANOS, EstadoTramite::Iniciado);
        $this->tramiteDe($p2, TipoTramite::CERTIFICADO_VIGENCIA, EstadoTramite::Aprobado);
        Tramite::factory()->count(20)->create(['tipo_tramite_id' => $this->tipo(TipoTramite::CAMBIO_DOMICILIO)->id]);

        $this->actingAs($empleado)->get('/panel/tramites')
            ->assertOk()
            ->assertSee('22 trámite(s)')
            ->assertSee('page=2');

        $this->actingAs($empleado)->get('/panel/tramites?q=Zabaleta')
            ->assertSee('1 trámite(s)')
            ->assertSee('Zabaleta')
            ->assertDontSee('Ibarra');

        $this->actingAs($empleado)->get('/panel/tramites?estado=aprobado')
            ->assertSee('1 trámite(s)')
            ->assertSee('Ibarra');
    }

    #[Test]
    public function rechaza_filtros_invalidos(): void
    {
        $this->actingAs($this->empleado())
            ->get('/panel/tramites?estado=cualquiera')
            ->assertSessionHasErrors('estado');
    }

    #[Test]
    public function pasar_a_revision_asigna_el_empleado_y_notifica(): void
    {
        $empleado = $this->empleado();
        $usuario = $this->profesional();
        $tramite = $this->tramiteDe($usuario, TipoTramite::VISADO_PLANOS, EstadoTramite::Iniciado);

        $this->actingAs($empleado)
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'en_revision'])
            ->assertRedirect(route('panel.tramites.show', $tramite))
            ->assertSessionHasNoErrors();

        $tramite->refresh();
        $this->assertSame(EstadoTramite::EnRevision, $tramite->estado);
        $this->assertSame($empleado->id, $tramite->empleado_id);
        $this->assertDatabaseHas('tramite_movimientos', ['tramite_id' => $tramite->id, 'estado_nuevo' => 'en_revision', 'user_id' => $empleado->id]);

        Notification::assertSentTo($usuario, EstadoTramiteActualizado::class, function ($n, $canales) use ($tramite) {
            return $n->tramite->is($tramite) && $canales === ['mail'];
        });
    }

    #[Test]
    public function no_permite_saltear_estados(): void
    {
        $tramite = $this->tramiteDe($this->profesional(), TipoTramite::VISADO_PLANOS, EstadoTramite::Iniciado);

        $this->actingAs($this->empleado())
            ->from(route('panel.tramites.show', $tramite))
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'aprobado'])
            ->assertSessionHasErrors(['estado' => 'No se puede pasar de "Iniciado" a "Aprobado".']);

        $this->assertSame(EstadoTramite::Iniciado, $tramite->fresh()->estado);
        Notification::assertNothingSent();
    }

    #[Test]
    public function observar_exige_observacion(): void
    {
        $tramite = $this->tramiteDe($this->profesional(), TipoTramite::VISADO_PLANOS, EstadoTramite::EnRevision);

        $this->actingAs($this->empleado())
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'observado', 'observacion' => ''])
            ->assertSessionHasErrors('observacion');

        $this->assertSame(EstadoTramite::EnRevision, $tramite->fresh()->estado);
    }

    #[Test]
    public function un_tramite_finalizado_no_se_puede_modificar(): void
    {
        $tramite = $this->tramiteDe($this->profesional(), TipoTramite::VISADO_PLANOS, EstadoTramite::Rechazado);

        $this->actingAs($this->empleado())
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'en_revision'])
            ->assertForbidden();
    }

    #[Test]
    public function el_profesional_no_puede_cambiar_estados(): void
    {
        $usuario = $this->profesional();
        $tramite = $this->tramiteDe($usuario, TipoTramite::VISADO_PLANOS, EstadoTramite::EnRevision);

        $this->actingAs($usuario)
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'aprobado'])
            ->assertForbidden();
    }

    #[Test]
    public function aprobar_un_certificado_lo_emite_con_codigo_de_verificacion(): void
    {
        $usuario = $this->profesional();
        $tramite = $this->tramiteDe($usuario, TipoTramite::CERTIFICADO_VIGENCIA, EstadoTramite::EnRevision);

        $this->actingAs($this->empleado())
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'aprobado'])
            ->assertSessionHasNoErrors();

        $certificado = Certificado::sole();
        $this->assertTrue($certificado->tramite->is($tramite));
        $this->assertSame($usuario->profesional->id, $certificado->profesional_id);
        $this->assertTrue($certificado->valido_hasta->isSameDay(today()->addDays(90)));
        $this->assertNotNull($tramite->fresh()->resuelto_en);
    }

    #[Test]
    public function no_aprueba_el_certificado_si_hay_cuotas_vencidas(): void
    {
        $usuario = $this->profesional();
        Cuota::factory()->vencida()->create(['profesional_id' => $usuario->profesional->id]);
        $tramite = $this->tramiteDe($usuario, TipoTramite::CERTIFICADO_VIGENCIA, EstadoTramite::EnRevision);

        $this->actingAs($this->empleado())
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'aprobado'])
            ->assertSessionHasErrors(['estado' => 'No se puede emitir el certificado: el profesional tiene 1 cuota(s) vencida(s) sin pagar.']);

        // La transacción se deshizo: ni certificado ni cambio de estado ni historial
        $this->assertDatabaseCount('certificados', 0);
        $this->assertSame(EstadoTramite::EnRevision, $tramite->fresh()->estado);
        $this->assertDatabaseCount('tramite_movimientos', 0);
    }

    #[Test]
    public function aprobar_cambio_de_domicilio_actualiza_la_ficha(): void
    {
        $usuario = $this->profesional(['domicilio' => 'Viejo 1', 'localidad' => 'Antes']);
        $tramite = $this->tramiteDe($usuario, TipoTramite::CAMBIO_DOMICILIO, EstadoTramite::EnRevision, [
            'datos' => ['domicilio' => 'Nuevo 2', 'localidad' => 'Después'],
        ]);

        $this->actingAs($this->empleado())
            ->patch(route('panel.tramites.estado', $tramite), ['estado' => 'aprobado'])
            ->assertSessionHasNoErrors();

        $profesional = $usuario->profesional->fresh();
        $this->assertSame('Nuevo 2', $profesional->domicilio);
        $this->assertSame('Después', $profesional->localidad);
    }

    #[Test]
    public function el_detalle_muestra_historial_y_formulario(): void
    {
        $tramite = $this->tramiteDe($this->profesional(), TipoTramite::VISADO_PLANOS, EstadoTramite::EnRevision);
        $tramite->movimientos()->create(['estado_nuevo' => 'en_revision', 'observacion' => 'Revisando la lámina 2']);

        $this->actingAs($this->empleado())->get(route('panel.tramites.show', $tramite))
            ->assertOk()
            ->assertSee('Revisando la lámina 2')
            ->assertSee('Cambiar estado');
    }

    #[Test]
    public function el_dashboard_cuenta_por_estado(): void
    {
        Tramite::factory()->count(3)->create();
        Tramite::factory()->enEstado(EstadoTramite::Observado)->create();

        $this->actingAs($this->empleado())->get('/panel')
            ->assertOk()
            ->assertViewHas('porEstado', fn ($c) => $c['iniciado'] === 3 && $c['observado'] === 1 && $c['aprobado'] === 0);
    }
}
