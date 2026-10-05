<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_pagina_de_inicio_y_el_login_son_publicos(): void
    {
        $this->get('/')->assertOk()->assertSee('Matrículas y trámites');
        $this->get('/login')->assertOk()->assertSee('Ingresar');
    }

    #[Test]
    public function el_empleado_entra_y_va_al_panel(): void
    {
        $empleado = User::factory()->empleado()->create(['password' => 'demo1234']);

        $this->post('/login', ['email' => $empleado->email, 'password' => 'demo1234'])
            ->assertRedirect(route('panel.inicio'));

        $this->assertAuthenticatedAs($empleado);
    }

    #[Test]
    public function el_profesional_entra_y_va_al_portal(): void
    {
        $usuario = $this->profesional();
        $usuario->update(['password' => 'demo1234']);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'demo1234'])
            ->assertRedirect(route('portal.inicio'));
    }

    #[Test]
    public function con_contrasenia_incorrecta_vuelve_con_error(): void
    {
        $usuario = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $usuario->email, 'password' => 'otra'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function bloquea_despues_de_cinco_intentos_fallidos(): void
    {
        $usuario = User::factory()->create(['password' => 'demo1234']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $usuario->email, 'password' => 'mal']);
        }

        // Ni siquiera con la contraseña correcta
        $this->post('/login', ['email' => $usuario->email, 'password' => 'demo1234'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    #[Test]
    public function las_pantallas_internas_piden_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/portal')->assertRedirect('/login');
    }

    #[Test]
    public function cada_rol_solo_entra_a_su_area(): void
    {
        $this->actingAs($this->profesional())->get('/panel')->assertForbidden();
        $this->actingAs($this->empleado())->get('/portal')->assertForbidden();
    }

    #[Test]
    public function cerrar_sesion(): void
    {
        $this->actingAs($this->empleado())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
