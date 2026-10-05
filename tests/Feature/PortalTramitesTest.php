<?php

namespace Tests\Feature;

use App\Enums\EstadoTramite;
use App\Models\TipoTramite;
use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalTramitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->crearTiposDeTramite();
    }

    #[Test]
    public function el_profesional_ve_su_inicio_y_el_formulario(): void
    {
        $usuario = $this->profesional(['matricula' => 'AR-5555']);

        $this->actingAs($usuario)->get('/portal')->assertOk()->assertSee('AR-5555');
        $this->actingAs($usuario)->get('/portal/tramites/nuevo')->assertOk()->assertSee('Visado de planos');
    }

    #[Test]
    public function inicia_un_visado_con_pdf_y_queda_el_historial(): void
    {
        $usuario = $this->profesional();
        $tipo = $this->tipo(TipoTramite::VISADO_PLANOS);

        $respuesta = $this->actingAs($usuario)->post('/portal/tramites', [
            'tipo_tramite_id' => $tipo->id,
            'descripcion' => 'Vivienda unifamiliar 120 m2',
            'adjuntos' => [UploadedFile::fake()->create('planos.pdf', 300, 'application/pdf')],
        ]);

        $tramite = Tramite::with(['adjuntos', 'movimientos'])->sole();
        $respuesta->assertRedirect(route('portal.tramites.show', $tramite));

        $this->assertSame(EstadoTramite::Iniciado, $tramite->estado);
        $this->assertMatchesRegularExpression('/^TR-\d{4}-\d{5}$/', $tramite->numero);
        $this->assertCount(1, $tramite->movimientos);
        $this->assertSame('planos.pdf', $tramite->adjuntos->first()->nombre_original);
        Storage::disk('local')->assertExists($tramite->adjuntos->first()->ruta);
    }

    #[Test]
    public function el_visado_sin_adjunto_no_se_acepta(): void
    {
        $usuario = $this->profesional();

        $this->actingAs($usuario)
            ->post('/portal/tramites', ['tipo_tramite_id' => $this->tipo(TipoTramite::VISADO_PLANOS)->id])
            ->assertSessionHasErrors('adjuntos');

        $this->assertDatabaseCount('tramites', 0);
    }

    #[Test]
    public function solo_acepta_pdf(): void
    {
        $usuario = $this->profesional();

        $this->actingAs($usuario)->post('/portal/tramites', [
            'tipo_tramite_id' => $this->tipo(TipoTramite::VISADO_PLANOS)->id,
            'adjuntos' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertSessionHasErrors('adjuntos.0');

        $this->assertDatabaseCount('tramites', 0);
    }

    #[Test]
    public function el_cambio_de_domicilio_pide_el_nuevo_domicilio(): void
    {
        $usuario = $this->profesional();
        $tipo = $this->tipo(TipoTramite::CAMBIO_DOMICILIO);

        $this->actingAs($usuario)->post('/portal/tramites', ['tipo_tramite_id' => $tipo->id])
            ->assertSessionHasErrors(['domicilio', 'localidad']);

        $this->actingAs($usuario)->post('/portal/tramites', [
            'tipo_tramite_id' => $tipo->id,
            'domicilio' => 'Calle Nueva 456',
            'localidad' => 'Puerto Demo',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['domicilio' => 'Calle Nueva 456', 'localidad' => 'Puerto Demo'], Tramite::sole()->datos);
    }

    #[Test]
    public function con_la_matricula_suspendida_no_puede_iniciar_tramites(): void
    {
        $usuario = $this->profesional(['estado' => 'suspendida', 'motivo_estado' => 'Deuda']);

        $this->actingAs($usuario)->get('/portal/tramites/nuevo')->assertForbidden();
        $this->actingAs($usuario)
            ->post('/portal/tramites', ['tipo_tramite_id' => $this->tipo(TipoTramite::CERTIFICADO_VIGENCIA)->id])
            ->assertForbidden();
    }

    #[Test]
    public function no_puede_ver_tramites_ni_adjuntos_de_otro_profesional(): void
    {
        $yo = $this->profesional();
        $otro = $this->profesional();
        $tramite = Tramite::factory()->create(['profesional_id' => $otro->profesional->id]);
        $adjunto = $tramite->adjuntos()->create(['nombre_original' => 'x.pdf', 'ruta' => 'tramites/x.pdf', 'tamanio' => 10]);

        $this->actingAs($yo)->get(route('portal.tramites.show', $tramite))->assertForbidden();
        $this->actingAs($yo)->get(route('adjuntos.descargar', $adjunto))->assertForbidden();
    }

    #[Test]
    public function descarga_su_propio_adjunto(): void
    {
        $usuario = $this->profesional();
        $tramite = Tramite::factory()->create(['profesional_id' => $usuario->profesional->id]);
        Storage::disk('local')->put('tramites/1/plano.pdf', '%PDF-1.4 prueba');
        $adjunto = $tramite->adjuntos()->create(['nombre_original' => 'plano.pdf', 'ruta' => 'tramites/1/plano.pdf', 'tamanio' => 15]);

        $this->actingAs($usuario)->get(route('adjuntos.descargar', $adjunto))
            ->assertOk()
            ->assertDownload('plano.pdf');
    }

    #[Test]
    public function responde_una_observacion_y_vuelve_a_revision(): void
    {
        $usuario = $this->profesional();
        $tramite = Tramite::factory()->enEstado(EstadoTramite::Observado)->create([
            'profesional_id' => $usuario->profesional->id,
            'tipo_tramite_id' => $this->tipo(TipoTramite::VISADO_PLANOS)->id,
        ]);

        $this->actingAs($usuario)->post(route('portal.tramites.responder', $tramite), [
            'comentario' => 'Adjunto los planos firmados.',
            'adjuntos' => [UploadedFile::fake()->create('planos-firmados.pdf', 100, 'application/pdf')],
        ])->assertRedirect(route('portal.tramites.show', $tramite));

        $tramite->refresh();
        $this->assertSame(EstadoTramite::EnRevision, $tramite->estado);
        $this->assertSame(1, $tramite->adjuntos()->count());
        $this->assertDatabaseHas('tramite_movimientos', [
            'tramite_id' => $tramite->id,
            'estado_anterior' => 'observado',
            'estado_nuevo' => 'en_revision',
            'observacion' => 'Adjunto los planos firmados.',
        ]);
        // No se le manda mail por algo que hizo él mismo
        Notification::assertNothingSent();
    }

    #[Test]
    public function no_puede_responder_si_el_tramite_no_esta_observado(): void
    {
        $usuario = $this->profesional();
        $tramite = Tramite::factory()->enEstado(EstadoTramite::EnRevision)->create(['profesional_id' => $usuario->profesional->id]);

        $this->actingAs($usuario)
            ->post(route('portal.tramites.responder', $tramite), ['comentario' => 'Un comentario cualquiera'])
            ->assertForbidden();
    }
}
