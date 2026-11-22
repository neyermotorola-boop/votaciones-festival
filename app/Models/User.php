<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\RestablecerContrasena;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Permisos que el administrador puede asignar a un usuario "gestor". */
    public const PERMISOS = [
        'ediciones' => 'Gestionar ediciones',
        'categorias' => 'Crear y editar categorías',
        'votaciones' => 'Gestionar votaciones y opciones',
        'resultados' => 'Ver votos y gráficos de resultados',
        'reportes' => 'Descargar reportes en Excel',
    ];

    public const ROLES = ['admin' => 'Administrador', 'gestor' => 'Gestor', 'votante' => 'Votante'];

    public const TIPOS_DOCUMENTO = [
        'CC' => 'Cédula',
        'CE' => 'C. extranjería',
        'TI' => 'T. identidad',
        'PA' => 'Pasaporte',
    ];

    protected $fillable = ['name', 'email', 'tipo_documento', 'documento', 'password', 'rol', 'permisos', 'activo'];

    protected static function booted(): void
    {
        // El correo canónico y el documento normalizado se calculan siempre al guardar.
        static::saving(function (User $u) {
            $u->email_canonico = static::canonizarEmail($u->email);
            $u->documento = static::normalizarDocumento($u->documento);
        });
    }

    /** Documento sin espacios, puntos ni guiones y en mayúsculas (null si queda vacío). */
    public static function normalizarDocumento(?string $doc): ?string
    {
        $doc = strtoupper(preg_replace('/[\s.\-]/', '', (string) $doc));

        return $doc === '' ? null : $doc;
    }

    /**
     * Correo "real": minúsculas, sin alias +etiqueta y, en Gmail, sin puntos.
     * Así a.b+1@gmail.com y ab@gmail.com son la misma persona.
     */
    public static function canonizarEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        if (! str_contains($email, '@')) {
            return $email === '' ? null : $email;
        }
        [$local, $dominio] = explode('@', $email, 2);
        $local = explode('+', $local, 2)[0];
        if ($dominio === 'googlemail.com') {
            $dominio = 'gmail.com';
        }
        if ($dominio === 'gmail.com') {
            $local = str_replace('.', '', $local);
        }

        return $local.'@'.$dominio;
    }

    /** Reglas del documento: formato y que no pertenezca ya a otra cuenta. Normaliza el valor del request antes de validar. */
    public static function reglasDocumento(bool $requerido = true, ?int $ignorarId = null): array
    {
        return [
            'tipo_documento' => [$requerido ? 'required' : 'nullable', \Illuminate\Validation\Rule::in(array_keys(static::TIPOS_DOCUMENTO))],
            'documento' => [
                'bail',
                $requerido ? 'required' : 'nullable',
                'regex:/^[A-Z0-9]{5,15}$/',
                // Estructura real según el tipo (CC, TI, CE, PA) y rechazo de números inventados.
                new \App\Rules\DocumentoValido,
                \Illuminate\Validation\Rule::unique('users', 'documento')->ignore($ignorarId),
            ],
        ];
    }

    public static function mensajesDocumento(): array
    {
        return [
            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'documento.required' => 'Ingresa tu número de documento.',
            'documento.regex' => 'El documento debe tener entre 5 y 15 letras o números, sin símbolos.',
            'documento.unique' => 'Este documento ya tiene una cuenta registrada. Solo se permite una cuenta por persona.',
        ];
    }

    /** Regla para que un mismo correo «real» (ignorando alias y puntos de Gmail) no cree dos cuentas. */
    public static function reglaEmailCanonicoUnico(?int $ignorarId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignorarId) {
            $existe = static::where('email_canonico', static::canonizarEmail($value))
                ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists();
            if ($existe) {
                $fail('Este correo ya tiene una cuenta registrada. Solo se permite una cuenta por persona.');
            }
        };
    }

    public function tieneDocumento(): bool
    {
        return filled($this->documento);
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permisos' => 'array',
            'activo' => 'boolean',
        ];
    }

    public function estaActivo(): bool
    {
        return $this->activo !== false;
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esGestor(): bool
    {
        return $this->rol === 'gestor';
    }

    /** El administrador puede todo; el gestor solo lo que se le asignó; el votante nada del panel. */
    public function puede(string $permiso): bool
    {
        if (! $this->estaActivo()) {
            return false;
        }

        return $this->esAdmin() || ($this->esGestor() && in_array($permiso, $this->permisos ?? [], true));
    }

    public function tieneAccesoPanel(): bool
    {
        return $this->estaActivo() && ($this->esAdmin() || ($this->esGestor() && ! empty($this->permisos)));
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }

    /**
     * Envía una notificación por correo sin afectar el flujo principal:
     * si el servidor de correo falla, se registra en el log y la acción
     * (registro, voto…) continúa normalmente.
     */
    public function notificarCorreo(Notification $notificacion): void
    {
        if (! config('festival.notificaciones_correo')) {
            return;
        }

        try {
            $this->notify($notificacion);
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el correo de notificación', [
                'user_id' => $this->id,
                'notificacion' => $notificacion::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }
}
