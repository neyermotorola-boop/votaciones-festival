<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['edicion_id', 'nombre', 'descripcion', 'icono', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    /** Inicial del nombre, usada como identificador visual de la categoría. */
    protected function inicial(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(mb_substr($this->nombre ?? '', 0, 1)));
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }

    public function votaciones(): HasMany
    {
        return $this->hasMany(Votacion::class);
    }
}
