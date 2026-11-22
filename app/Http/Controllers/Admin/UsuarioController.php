<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gestión de usuarios y de sus permisos. Solo el administrador (middleware EsAdmin). */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()->withCount('votos')
            ->when($request->filled('rol'), fn ($q) => $q->where('rol', $request->input('rol')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->input('q').'%')
                ->orWhere('email', 'like', '%'.$request->input('q').'%')
                ->orWhere('documento', 'like', '%'.(User::normalizarDocumento($request->input('q')) ?? '@@').'%')))
            ->orderByRaw("CASE rol WHEN 'admin' THEN 0 WHEN 'gestor' THEN 1 ELSE 2 END")
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('admin.usuarios.form', ['usuario' => new User(['rol' => 'gestor', 'permisos' => []])]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $usuario = User::create($datos + ['activo' => true]);
        Bitacora::registrar('Crear usuario', "{$usuario->email} ({$usuario->rol})");

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $this->validar($request, $usuario);
        if ($usuario->is($request->user()) && $datos['rol'] !== 'admin') {
            return back()->withInput()->withErrors(['rol' => 'No puedes quitarte a ti mismo el rol de administrador.']);
        }
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }
        $usuario->update($datos);
        Bitacora::registrar('Editar usuario', "{$usuario->email} ({$usuario->rol})");

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    /** Activa o desactiva la cuenta (no se borra: conserva su historial de votos). */
    public function estado(Request $request, User $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }
        $usuario->update(['activo' => ! $usuario->estaActivo()]);
        Bitacora::registrar($usuario->activo ? 'Activar usuario' : 'Desactivar usuario', $usuario->email);

        return back()->with('ok', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge(['documento' => User::normalizarDocumento($request->input('documento'))]);
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($usuario?->id), User::reglaEmailCanonicoUnico($usuario?->id)],
            // El documento es obligatorio para los votantes (identifica su voto); opcional para el personal.
            ...User::reglasDocumento($request->input('rol') === 'votante', $usuario?->id),
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8'],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => [Rule::in(array_keys(User::PERMISOS))],
        ], [
            'permisos.*.in' => 'Alguno de los permisos seleccionados no es válido.',
        ] + User::mensajesDocumento());
        // Solo el gestor usa permisos individuales; admin tiene todos y el votante ninguno.
        $datos['permisos'] = $datos['rol'] === 'gestor' ? array_values($datos['permisos'] ?? []) : null;

        return $datos;
    }
}
