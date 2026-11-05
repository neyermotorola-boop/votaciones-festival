<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    protected $fillable = ['user_id', 'accion', 'detalle'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function registrar(string $accion, ?string $detalle = null): void
    {
        static::create(['user_id' => auth()->id(), 'accion' => $accion, 'detalle' => $detalle]);
    }
}
