<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionales', function (Blueprint $table) {
            $table->id();
            // Cuenta para entrar al portal (un profesional puede no tener usuario todavía)
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('matricula', 20)->unique();
            $table->string('apellido', 80);
            $table->string('nombre', 80);
            // CUIT guardado solo con dígitos (11); se formatea al mostrarlo
            $table->char('cuit', 11)->unique();
            $table->string('email', 150);
            $table->string('telefono', 40)->nullable();
            $table->string('domicilio', 150);
            $table->string('localidad', 80);
            $table->string('profesion', 100);
            $table->date('fecha_matriculacion');
            $table->string('estado', 20)->default('activa')->index();
            $table->string('motivo_estado', 255)->nullable();
            $table->timestamps();

            $table->index(['apellido', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};
