<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Votacion extends Model
{
    public const BORRADOR = 'borrador';
    public const ABIERTA = 'abierta';
    public const CERRADA = 'cerrada';

    protected $table = 'votaciones';

    protected $fillable = [
        'categoria_id', 'titulo', 'descripcion', 'estado',
        'apertura', 'cierre', 'votos_por_usuario', 'resultados_publicos',
    ];

    protected $casts = [
        'apertura' => 'datetime',
        'cierre' => 'datetime',
        'resultados_publicos' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }

    /** Regla RN-02: solo se vota si está abierta y dentro del periodo definido. */
    public function estaAbierta(): bool
    {
        if ($this->estado !== self::ABIERTA) {
            return false;
        }
        if ($this->apertura && $this->apertura->isFuture()) {
            return false;
        }
        if ($this->cierre && $this->cierre->isPast()) {
            return false;
        }

        return true;
    }

    /** Estado efectivo para mostrar (considera fechas). */
    public function estadoEfectivo(): string
    {
        if ($this->estado === self::ABIERTA && $this->cierre && $this->cierre->isPast()) {
            return self::CERRADA;
        }
        if ($this->estado === self::ABIERTA && $this->apertura && $this->apertura->isFuture()) {
            return 'programada';
        }

        return $this->estado;
    }

    public function votosDe(User $user): int
    {
        return $this->votos()->where('user_id', $user->id)->count();
    }
}
