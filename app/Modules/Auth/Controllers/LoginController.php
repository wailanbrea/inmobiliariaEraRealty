<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        audit()->log(AuditAction::Login);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Se registra ANTES de cerrar la sesion: despues del logout ya no hay
        // usuario autenticado y el apunte quedaria sin autor.
        audit()->log(AuditAction::Logout);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
