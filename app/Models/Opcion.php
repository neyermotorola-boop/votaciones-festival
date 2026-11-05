<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opcion extends Model
{
    protected $table = 'opciones';

    protected $fillable = ['votacion_id', 'nombre', 'descripcion', 'imagen'];

    public function votacion(): BelongsTo
    {
        return $this->belongsTo(Votacion::class);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }
}
