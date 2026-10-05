<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            // Período en formato AAAA-MM
            $table->char('periodo', 7);
            $table->decimal('importe', 10, 2);
            $table->date('vencimiento');
            $table->dateTime('pagada_en')->nullable();
            $table->string('medio_pago', 30)->nullable();
            $table->foreignId('registrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Una sola cuota por profesional y período (el comando de generación es idempotente)
            $table->unique(['profesional_id', 'periodo']);
            $table->index(['vencimiento', 'pagada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
