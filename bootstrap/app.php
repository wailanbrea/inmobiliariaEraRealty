<?php

use App\Http\Middleware\DetectBrowserLocale;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // Solo actua en la raiz y en la primera visita; en el resto de rutas
        // devuelve la peticion intacta. Ver la cabecera de la clase.
        $middleware->web(append: [DetectBrowserLocale::class]);

        // Una contrasena generada por otra persona solo puede servir para
        // entrar una vez. Deja pasar la pantalla de cambio y el cierre de
        // sesion; todo lo demas redirige alli.
        $middleware->web(append: [ForcePasswordChange::class]);

        $middleware->alias([
            'set.locale' => SetLocale::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Un formulario de login abierto durante mucho tiempo puede conservar
        // un token CSRF anterior a la sesion actual. Se renueva solo el login;
        // el resto del panel mantiene el 419 para no ocultar errores CSRF.
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->isMethod('post') && $request->is('admin/login')) {
                return redirect()
                    ->route('admin.login')
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('admin/auth.session_expired')]);
            }
        });
    })->create();
