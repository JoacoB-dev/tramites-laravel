<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Support\Cuit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('profesionales')]
#[Fillable([
    'user_id', 'matricula', 'apellido', 'nombre', 'cuit', 'email', 'telefono',
    'domicilio', 'localidad', 'profesion', 'fecha_matriculacion', 'estado', 'motivo_estado',
])]
class Profesional extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estado' => EstadoMatricula::class,
            'fecha_matriculacion' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class);
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class);
    }

    /** "Pérez, María" */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => "{$this->apellido}, {$this->nombre}");
    }

    protected function cuitFormateado(): Attribute
    {
        return Attribute::get(fn () => Cuit::formatear($this->cuit));
    }

    /** Siempre se guarda solo con dígitos, venga como venga. */
    protected function cuit(): Attribute
    {
        return Attribute::set(fn (?string $valor) => Cuit::normalizar($valor));
    }

    public function estaActiva(): bool
    {
        return $this->estado === EstadoMatricula::Activa;
    }

    /** Al día = sin cuotas vencidas impagas. */
    public function estaAlDia(): bool
    {
        return ! $this->cuotas()->vencidas()->exists();
    }

    /** Búsqueda libre por matrícula, apellido, nombre o CUIT. */
    #[Scope]
    protected function buscar(Builder $query, ?string $texto): void
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return;
        }

        $query->where(function (Builder $q) use ($texto) {
            $q->where('matricula', 'like', "%{$texto}%")
                ->orWhere('apellido', 'like', "%{$texto}%")
                ->orWhere('nombre', 'like', "%{$texto}%");

            $digitos = Cuit::normalizar($texto);
            if (strlen($digitos) >= 4) {
                $q->orWhere('cuit', 'like', "%{$digitos}%");
            }
        });
    }
}
