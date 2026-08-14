<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Settings\Services\MailConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(Request $request, MailConfigService $mailConfig): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);

        $userIsActive = User::query()->where('email', (string) $request->string('email'))->where('is_active', true)->exists();
        if ($userIsActive) {
            try {
                $mailConfig->apply();
                Password::sendResetLink($request->only('email'));
            } catch (Throwable $exception) {
                Log::error('No se pudo enviar la recuperacion de acceso.', ['exception' => $exception->getMessage()]);
            }
        }

        return back()->with('status', __('admin/auth.forgot.sent'));
    }
}
