<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/**
 * Cuentas anteriores sin documento deben registrarlo antes de votar.
 * Una vez guardado no se puede cambiar desde aquí (solo un administrador puede corregirlo).
 */
class DocumentoController extends Controller
{
    public function form(Request $request)
    {
        if ($request->user()->tieneDocumento()) {
            return redirect()->route('mis-votos');
        }

        return view('documento');
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        abort_if($user->tieneDocumento(), 403, 'Tu documento ya está registrado y no se puede cambiar.');

        $request->merge(['documento' => User::normalizarDocumento($request->input('documento'))]);
        $datos = $request->validate(User::reglasDocumento(), User::mensajesDocumento());

        try {
            $user->update($datos);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors(['documento' => User::mensajesDocumento()['documento.unique']]);
        }
        Bitacora::registrar('Documento registrado', $user->email);

        return redirect()->intended(route('home'))->with('ok', 'Documento registrado. Ya puedes votar.');
    }
}
