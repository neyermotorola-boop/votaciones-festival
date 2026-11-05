<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voto extends Model
{
    protected $table = 'votos';

    protected $fillable = ['user_id', 'votacion_id', 'opcion_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
