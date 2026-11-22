<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Opcion extends Model
{
    /** Disco donde se guardan las fotos de los participantes (public/uploads). */
    public const DISCO = 'uploads';

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

    /** URL pública de la foto, o null si el participante es solo texto. */
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn () => $this->imagen ? asset('uploads/'.$this->imagen) : null);
    }

    /** Inicial del nombre, se usa cuando no hay foto. */
    protected function inicial(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(mb_substr($this->nombre ?? '', 0, 1)));
    }

    public function borrarImagen(): void
    {
        if ($this->imagen) {
            Storage::disk(self::DISCO)->delete($this->imagen);
        }
    }
}
