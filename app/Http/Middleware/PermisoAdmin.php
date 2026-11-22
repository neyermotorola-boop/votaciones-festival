<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por permiso. Uso en rutas: PermisoAdmin::class.':categorias'
 * El permiso especial "panel" solo exige tener acceso al panel de administración.
 */
class PermisoAdmin
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $user = $request->user();
        $ok = $permiso === 'panel' ? $user?->tieneAccesoPanel() : $user?->puede($permiso);
        abort_unless($ok, 403, 'No tienes permiso para acceder a esta sección.');

        return $next($request);
    }
}
