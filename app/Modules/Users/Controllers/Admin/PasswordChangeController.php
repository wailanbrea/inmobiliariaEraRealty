<?php

namespace App\Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], [
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ]);

        // No se acepta repetir la misma: si la generada la conoce quien la
        // creo, dejarla puesta anula el sentido de obligar al cambio.
        if (Hash::check($datos['password'], $request->user()->password)) {
            return back()->withErrors(['password' => __('admin/users.errors.same_password')]);
        }

        $request->user()->forceFill([
            'password' => $datos['password'],
            'must_change_password' => false,
        ])->save();

        // Se renueva la sesion: si la clave anterior circulo por WhatsApp,
        // conviene que ninguna sesion abierta con ella siga viva.
        $request->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', __('admin/users.password_changed'));
    }
}
