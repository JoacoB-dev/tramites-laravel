<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            // Código que cualquiera puede verificar en /verificar/{codigo}
            $table->string('codigo', 20)->unique();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            $table->foreignId('tramite_id')->nullable()->constrained('tramites')->nullOnDelete();
            $table->dateTime('emitido_en');
            $table->date('valido_hasta');
            $table->dateTime('anulado_en')->nullable();
            $table->string('motivo_anulacion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
