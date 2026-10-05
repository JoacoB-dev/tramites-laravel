<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();
            // Número visible para el profesional (TR-2026-00001). Se completa apenas se crea.
            $table->string('numero', 20)->nullable()->unique();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('tipo_tramite_id')->constrained('tipos_tramite')->restrictOnDelete();
            $table->string('estado', 20)->default('iniciado');
            $table->text('descripcion')->nullable();
            // Datos propios de cada tipo (ej. nuevo domicilio en un cambio de domicilio)
            $table->json('datos')->nullable();
            // Empleado/a que tomó el trámite
            $table->foreignId('empleado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
        });

        Schema::create('tramite_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->text('observacion')->nullable();
            // El historial no se edita: solo fecha de creación
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tramite_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre_original', 255);
            // Ruta dentro del disco privado "local" (no se publica en /public)
            $table->string('ruta', 255);
            $table->unsignedInteger('tamanio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramite_adjuntos');
        Schema::dropIfExists('tramite_movimientos');
        Schema::dropIfExists('tramites');
    }
};
