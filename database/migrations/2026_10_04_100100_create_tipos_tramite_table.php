<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_tramite', function (Blueprint $table) {
            $table->id();
            // Código fijo que usa el código para aplicar efectos (ver App\Models\TipoTramite)
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_adjunto')->default(false);
            $table->decimal('arancel', 10, 2)->default(0);
            $table->unsignedTinyInteger('dias_estimados')->default(5);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_tramite');
    }
};
