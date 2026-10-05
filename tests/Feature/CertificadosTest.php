<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Profesional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificadosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function verificacion_publica_de_un_certificado_valido(): void
    {
        $c = Certificado::factory()->create(['codigo' => 'ABCD-EFGH-JKLM']);

        $this->get('/verificar/ABCD-EFGH-JKLM')
            ->assertOk()
            ->assertSee('data-resultado="valido"', false)
            ->assertSee($c->profesional->matricula);
    }

    #[Test]
    public function acepta_el_codigo_en_minusculas_desde_el_formulario(): void
    {
        Certificado::factory()->create(['codigo' => 'ABCD-EFGH-JKLM']);

        $this->get('/verificar?codigo=abcd-efgh-jklm')->assertRedirect('/verificar/ABCD-EFGH-JKLM');
        $this->get('/verificar/abcd-efgh-jklm')->assertSee('data-resultado="valido"', false);
    }

    #[Test]
    public function informa_codigo_inexistente_vencido_y_anulado(): void
    {
        Certificado::factory()->vencido()->create(['codigo' => 'VENC-IDOX-XXXX']);
        Certificado::factory()->anulado()->create(['codigo' => 'ANUL-ADOX-XXXX']);

        $this->get('/verificar/NOEX-ISTE-XXXX')->assertOk()->assertSee('data-resultado="inexistente"', false);
        $this->get('/verificar/VENC-IDOX-XXXX')->assertSee('data-resultado="vencido"', false);
        $this->get('/verificar/ANUL-ADOX-XXXX')->assertSee('data-resultado="anulado"', false)->assertSee('Emitido por error');
    }

    #[Test]
    public function avisa_si_la_matricula_cambio_despues_de_emitido(): void
    {
        $p = Profesional::factory()->suspendida()->create();
        Certificado::factory()->create(['codigo' => 'SUSP-ENDI-DAXX', 'profesional_id' => $p->id]);

        $this->get('/verificar/SUSP-ENDI-DAXX')->assertSee('la matrícula cambió de estado');
    }

    #[Test]
    public function el_profesional_descarga_su_certificado_en_pdf(): void
    {
        $usuario = $this->profesional();
        $c = Certificado::factory()->create(['profesional_id' => $usuario->profesional->id]);

        $respuesta = $this->actingAs($usuario)->get(route('certificados.descargar', $c));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    #[Test]
    public function otro_profesional_no_puede_descargarlo(): void
    {
        $c = Certificado::factory()->create();

        $this->actingAs($this->profesional())->get(route('certificados.descargar', $c))->assertForbidden();
    }

    #[Test]
    public function solo_el_admin_anula_y_debe_indicar_motivo(): void
    {
        $c = Certificado::factory()->create();

        $this->actingAs($this->empleado())
            ->post(route('panel.certificados.anular', $c), ['motivo_anulacion' => 'Error'])
            ->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('panel.certificados.anular', $c), [])->assertSessionHasErrors('motivo_anulacion');
        $this->actingAs($admin)->post(route('panel.certificados.anular', $c), ['motivo_anulacion' => 'Datos mal cargados'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($c->fresh()->estaAnulado());
    }
}
