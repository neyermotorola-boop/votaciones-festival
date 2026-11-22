<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voto extends Model
{
    protected $table = 'votos';

    protected $fillable = ['user_id', 'edicion_id', 'categoria_id', 'votacion_id', 'opcion_id', 'documento'];

    /**
     * Integridad: edición y categoría del voto se derivan siempre de su votación y la opción debe pertenecerle,
     * de modo que ningún código pueda registrar un voto incoherente con la regla un voto por categoría y edición.
     */
    protected static function booted(): void
    {
        static::creating(function (Voto $voto) {
            $votacion = Votacion::with('categoria')->findOrFail($voto->votacion_id);
            if (! Opcion::where('id', $voto->opcion_id)->where('votacion_id', $votacion->id)->exists()) {
                throw new \InvalidArgumentException('La opción no pertenece a la votación del voto.');
            }
            // Se guarda el documento de quien votó (identifica a la persona aunque cambie de cuenta o de correo).
            $voto->documento = User::whereKey($voto->user_id)->value('documento');
            $voto->categoria_id = $votacion->categoria_id;
            $voto->edicion_id = $votacion->categoria->edicion_id;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function votacion(): BelongsTo
    {
        return $this->belongsTo(Votacion::class);
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class);
    }
}
