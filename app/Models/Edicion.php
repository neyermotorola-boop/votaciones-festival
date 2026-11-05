<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edicion extends Model
{
    protected $table = 'ediciones';

    protected $fillable = ['nombre', 'anio', 'descripcion', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public static function activa(): ?self
    {
        return static::where('activa', true)->first();
    }
}
