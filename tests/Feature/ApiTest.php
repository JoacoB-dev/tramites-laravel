<?php

namespace Tests\Feature;

use App\Enums\EstadoTramite;
use App\Models\Profesional;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Cuit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_devuelve_un_token_que_sirve_para_consultar(): void
    {
        $usuario = User::factory()->empleado()->create(['password' => 'demo1234']);
        Profesional::factory()->create(['matricula' => 'IC-7777']);

        $token = $this->postJson('/api/v1/login', ['email' => $usuario->email, 'password' => 'demo1234'])
            ->assertCreated()
            ->assertJsonStructure(['token', 'tipo', 'vence', 'usuario' => ['nombre', 'rol']])
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/matriculas/IC-7777')
            ->assertOk()
            ->assertJsonPath('data.matricula', 'IC-7777');
    }

    #[Test]
    public function login_con_credenciales_malas_da_422(): void
    {
        $usuario = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $usuario->email, 'password' => 'mal'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function sin_token_da_401(): void
    {
        $this->getJson('/api/v1/matriculas/IC-1')->assertUnauthorized();
    }

    #[Test]
    public function consulta_estado_de_matricula_sin_exponer_datos_personales(): void
    {
        Sanctum::actingAs(User::factory()->empleado()->create());
        $p = Profesional::factory()->suspendida()->create(['matricula' => 'AR-1200']);

        $this->getJson('/api/v1/matriculas/ar-1200')
            ->assertOk()
            ->assertJsonPath('data.estado', 'suspendida')
            ->assertJsonPath('data.habilitado', false)
            ->assertJsonPath('data.cuit', $p->cuit_formateado)
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.domicilio');
    }

    #[Test]
    public function busca_por_cuit_y_valida_el_formato(): void
    {
        Sanctum::actingAs(User::factory()->empleado()->create());
        Profesional::factory()->create(['matricula' => 'IC-3000', 'cuit' => '20123456786']);

        $this->getJson('/api/v1/matriculas?cuit=20-12345678-6')->assertOk()->assertJsonPath('data.matricula', 'IC-3000');
        $this->getJson('/api/v1/matriculas?cuit=20-12345678-5')->assertUnprocessable();
        $this->getJson('/api/v1/matriculas?cuit='.Cuit::desdeDni(30123456, '27'))->assertNotFound();
    }

    #[Test]
    public function matricula_inexistente_da_404_en_json(): void
    {
        Sanctum::actingAs(User::factory()->empleado()->create());

        $this->getJson('/api/v1/matriculas/XX-0000')->assertNotFound()->assertJsonStructure(['message']);
    }

    #[Test]
    public function el_profesional_solo_lista_sus_tramites(): void
    {
        $usuario = $this->profesional();
        Tramite::factory()->count(2)->create(['profesional_id' => $usuario->profesional->id]);
        $ajeno = Tramite::factory()->create();

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/tramites')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/tramites/'.$ajeno->numero)->assertForbidden();
    }

    #[Test]
    public function el_staff_lista_todos_y_filtra_por_estado(): void
    {
        Tramite::factory()->count(3)->create();
        $observado = Tramite::factory()->enEstado(EstadoTramite::Observado)->create();
        Sanctum::actingAs($this->empleado());

        $this->getJson('/api/v1/tramites')->assertJsonCount(4, 'data')->assertJsonPath('meta.total', 4);
        $this->getJson('/api/v1/tramites?estado=observado')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero', $observado->numero);
    }

    #[Test]
    public function detalle_de_tramite_con_historial(): void
    {
        $usuario = $this->profesional();
        $tramite = Tramite::factory()->create(['profesional_id' => $usuario->profesional->id]);
        $tramite->movimientos()->create(['estado_nuevo' => 'iniciado', 'observacion' => 'Inicio']);
        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/tramites/'.$tramite->numero)
            ->assertOk()
            ->assertJsonPath('data.estado', 'iniciado')
            ->assertJsonPath('data.historial.0.observacion', 'Inicio');
    }

    #[Test]
    public function logout_revoca_el_token(): void
    {
        $usuario = User::factory()->create(['password' => 'demo1234']);
        $token = $this->postJson('/api/v1/login', ['email' => $usuario->email, 'password' => 'demo1234'])->json('token');

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function el_login_de_la_api_tiene_limite_de_intentos(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => $usuario->email, 'password' => 'mal'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', ['email' => $usuario->email, 'password' => 'mal'])->assertTooManyRequests();
    }
}
