<?php

namespace Tests\Feature;

use App\Models\Cuota;
use App\Models\Profesional;
use App\Services\GeneradorCuotas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CuotasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registra_un_pago_por_ajax(): void
    {
        $empleado = $this->empleado();
        $cuota = Cuota::factory()->vencida()->create();

        $this->actingAs($empleado)
            ->postJson(route('panel.cuotas.pago', $cuota), ['medio_pago' => 'transferencia', 'fecha_pago' => today()->toDateString()])
            ->assertOk()
            ->assertJsonPath('cuota.estado', 'pagada')
            ->assertJsonPath('cuota.medio_pago', 'Transferencia bancaria');

        $cuota->refresh();
        $this->assertTrue($cuota->estaPagada());
        $this->assertSame($empleado->id, $cuota->registrada_por);
    }

    #[Test]
    public function valida_el_medio_de_pago_y_la_fecha(): void
    {
        $cuota = Cuota::factory()->create();

        $this->actingAs($this->empleado())
            ->postJson(route('panel.cuotas.pago', $cuota), ['medio_pago' => 'bitcoin', 'fecha_pago' => today()->addDay()->toDateString()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medio_pago', 'fecha_pago']);
    }

    #[Test]
    public function no_se_paga_dos_veces(): void
    {
        $cuota = Cuota::factory()->pagada()->create();

        $this->actingAs($this->empleado())
            ->postJson(route('panel.cuotas.pago', $cuota), ['medio_pago' => 'efectivo'])
            ->assertForbidden();
    }

    #[Test]
    public function el_profesional_no_registra_pagos(): void
    {
        $usuario = $this->profesional();
        $cuota = Cuota::factory()->create(['profesional_id' => $usuario->profesional->id]);

        $this->actingAs($usuario)
            ->postJson(route('panel.cuotas.pago', $cuota), ['medio_pago' => 'efectivo'])
            ->assertForbidden();
    }

    #[Test]
    public function el_listado_filtra_vencidas(): void
    {
        $p1 = Profesional::factory()->create(['apellido' => 'Moroso']);
        $p2 = Profesional::factory()->create(['apellido' => 'Cumplidor']);
        Cuota::factory()->vencida()->create(['profesional_id' => $p1->id]);
        Cuota::factory()->vencida()->pagada()->create(['profesional_id' => $p2->id]);

        $this->actingAs($this->empleado())->get('/panel/cuotas?estado=vencida')
            ->assertOk()->assertSee('Moroso')->assertDontSee('Cumplidor');
    }

    #[Test]
    public function el_profesional_ve_su_deuda_en_el_portal(): void
    {
        $usuario = $this->profesional();
        Cuota::factory()->vencida()->create(['profesional_id' => $usuario->profesional->id, 'importe' => 18500]);

        $this->actingAs($usuario)->get('/portal/cuotas')
            ->assertOk()
            ->assertSee('Tenés cuotas vencidas')
            ->assertSee('18.500,00');
    }

    #[Test]
    public function el_comando_genera_cuotas_solo_para_activos_y_es_idempotente(): void
    {
        Profesional::factory()->count(3)->create();
        Profesional::factory()->suspendida()->create();

        $this->artisan('cuotas:generar', ['periodo' => '2026-11'])
            ->expectsOutput('Período 2026-11: 3 cuota(s) generada(s).')
            ->assertSuccessful();

        $this->artisan('cuotas:generar', ['periodo' => '2026-11'])
            ->expectsOutput('Período 2026-11: 0 cuota(s) generada(s).');

        $this->assertDatabaseCount('cuotas', 3);
        $this->assertSame('2026-11-10', Cuota::first()->vencimiento->toDateString());
    }

    #[Test]
    public function el_comando_rechaza_periodos_invalidos(): void
    {
        $this->artisan('cuotas:generar', ['periodo' => '2026-13'])->assertFailed();

        $this->expectException(\InvalidArgumentException::class);
        app(GeneradorCuotas::class)->generar('noviembre');
    }
}
