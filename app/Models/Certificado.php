<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['codigo', 'profesional_id', 'tramite_id', 'emitido_en', 'valido_hasta', 'anulado_en', 'motivo_anulacion'])]
class Certificado extends Model
{
    use HasFactory;

    // Sin 0/O ni 1/I para que el código se pueda dictar o tipear sin confusiones
    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected function casts(): array
    {
        return [
            'emitido_en' => 'datetime',
            'valido_hasta' => 'date',
            'anulado_en' => 'datetime',
        ];
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    /** Código aleatorio con formato XXXX-XXXX-XXXX que no exista todavía. */
    public static function generarCodigo(): string
    {
        do {
            $grupos = [];
            for ($g = 0; $g < 3; $g++) {
                $grupo = '';
                for ($i = 0; $i < 4; $i++) {
                    $grupo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
                }
                $grupos[] = $grupo;
            }
            $codigo = implode('-', $grupos);
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /** El usuario puede tipear el código en minúsculas o con espacios. */
    public static function normalizarCodigo(string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', $codigo) ?? '');
    }

    public function estaAnulado(): bool
    {
        return $this->anulado_en !== null;
    }

    public function estaVencido(): bool
    {
        return $this->valido_hasta->lt(today());
    }

    public function esValido(): bool
    {
        return ! $this->estaAnulado() && ! $this->estaVencido();
    }

    public function urlVerificacion(): string
    {
        return route('verificar.show', $this->codigo);
    }
}
