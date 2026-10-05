<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tipos_tramite')]
#[Fillable(['codigo', 'nombre', 'descripcion', 'requiere_adjunto', 'arancel', 'dias_estimados', 'activo'])]
class TipoTramite extends Model
{
    use HasFactory;

    // Códigos con comportamiento propio al aprobarse (ver GestorTramites::aplicarEfectos)
    public const VISADO_PLANOS = 'visado_planos';

    public const CERTIFICADO_VIGENCIA = 'certificado_vigencia';

    public const CAMBIO_DOMICILIO = 'cambio_domicilio';

    protected function casts(): array
    {
        return [
            'requiere_adjunto' => 'boolean',
            'activo' => 'boolean',
            'arancel' => 'decimal:2',
        ];
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class);
    }

    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true)->orderBy('nombre');
    }

    public function esCambioDomicilio(): bool
    {
        return $this->codigo === self::CAMBIO_DOMICILIO;
    }

    public function esCertificado(): bool
    {
        return $this->codigo === self::CERTIFICADO_VIGENCIA;
    }
}
