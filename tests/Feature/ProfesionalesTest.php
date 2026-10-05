<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfesionalesTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'matricula' => 'ic-4321',
            'apellido' => 'Pérez',
            'nombre' => 'Juan',
            'cuit' => '20-12345678-6',
            'email' => 'juan.perez@example.com',
            'telefono' => '11 4444-5555',
            'domicilio' => 'San Martín 100',
            'localidad' => 'Villa Ficticia',
            'profesion' => 'Ingeniero/a civil',
            'fecha_matriculacion' => '2020-05-04',
        ];
    }

    #[Test]
    public function alta_de_profesional_normaliza_cuit_y_matricula(): void
    {
        $this->actingAs($this->empleado())
            ->post(route('panel.profesionales.store'), $this->datos())
            ->assertSessionHasNoErrors();

        $p = Profesional::sole();
        $this->assertSame('IC-4321', $p->matricula);
        $this->assertSame('20123456786', $p->cuit);
        $this->assertSame('20-12345678-6', $p->cuit_formateado);
        $this->assertSame(EstadoMatricula::Activa, $p->estado);
        $this->assertNull($p->user_id);
    }

    #[Test]
    public function rechaza_cuit_con_digito_verificador_incorrecto(): void
    {
        $this->actingAs($this->empleado())
            ->post(route('panel.profesionales.store'), $this->datos(['cuit' => '20-12345678-5']))
            ->assertSessionHasErrors('cuit');

        $this->assertDatabaseCount('profesionales', 0);
    }

    #[Test]
    public function no_permite_matricula_ni_cuit_repetidos(): void
    {
        Profesional::factory()->create(['matricula' => 'IC-4321', 'cuit' => '20123456786']);

        $this->actingAs($this->empleado())
            ->post(route('panel.profesionales.store'), $this->datos())
            ->assertSessionHasErrors(['matricula', 'cuit']);
    }

    #[Test]
    public function puede_crear_el_acceso_al_portal(): void
    {
        $this->actingAs($this->empleado())
            ->post(route('panel.profesionales.store'), $this->datos(['crear_acceso' => '1']))
            ->assertSessionHas('ok', fn (string $msj) => str_contains($msj, 'contraseña temporal'));

        $usuario = User::where('email', 'juan.perez@example.com')->sole();
        $this->assertTrue($usuario->esProfesional());
        $this->assertTrue(Profesional::sole()->user->is($usuario));
    }

    #[Test]
    public function edicion_ignora_su_propia_matricula_en_unique(): void
    {
        $p = Profesional::factory()->create(['matricula' => 'IC-4321', 'cuit' => '20123456786']);

        $this->actingAs($this->empleado())
            ->put(route('panel.profesionales.update', $p), $this->datos(['telefono' => '11 0000-0000']))
            ->assertSessionHasNoErrors();

        $this->assertSame('11 0000-0000', $p->fresh()->telefono);
    }

    #[Test]
    public function listado_busca_por_cuit_y_apellido(): void
    {
        Profesional::factory()->create(['apellido' => 'Gutiérrez', 'cuit' => '20123456786']);
        Profesional::factory()->create(['apellido' => 'Fernández']);

        $empleado = $this->empleado();
        $this->actingAs($empleado)->get('/panel/profesionales?q=20-12345678')
            ->assertOk()->assertSee('Gutiérrez')->assertDontSee('Fernández');
        $this->actingAs($empleado)->get('/panel/profesionales?q=Fern')
            ->assertSee('Fernández')->assertDontSee('Gutiérrez');
    }

    #[Test]
    public function solo_el_admin_suspende_matriculas_y_con_motivo(): void
    {
        $p = Profesional::factory()->create();

        $this->actingAs($this->empleado())
            ->patch(route('panel.profesionales.estado', $p), ['estado' => 'suspendida', 'motivo' => 'Deuda'])
            ->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)
            ->patch(route('panel.profesionales.estado', $p), ['estado' => 'suspendida'])
            ->assertSessionHasErrors('motivo');

        $this->actingAs($admin)
            ->patch(route('panel.profesionales.estado', $p), ['estado' => 'suspendida', 'motivo' => 'Deuda de 6 cuotas'])
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame(EstadoMatricula::Suspendida, $p->estado);
        $this->assertSame('Deuda de 6 cuotas', $p->motivo_estado);
    }

    #[Test]
    public function la_ficha_se_ve_en_el_panel(): void
    {
        $p = Profesional::factory()->create(['apellido' => 'Rossi']);

        $this->actingAs($this->empleado())->get(route('panel.profesionales.show', $p))
            ->assertOk()->assertSee('Rossi')->assertSee($p->cuit_formateado);
    }
}
