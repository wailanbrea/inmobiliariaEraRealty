<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a cambiar la contrasena antes de usar el panel.
 *
 * Se activa cuando un super_admin crea una cuenta o restablece una clave: la
 * contrasena generada la conoce quien la genero, asi que solo puede servir
 * para entrar una vez.
 *
 * El campo 'must_change_password' existia en la tabla desde la Fase 0 pero
 * nadie lo leia — solo se ponia a false al restablecer por correo. Es decir,
 * habia una bandera de seguridad que no hacia absolutamente nada.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario?->must_change_password) {
            return $next($request);
        }

        // Estas rutas tienen que seguir siendo accesibles, o el usuario se
        // quedaria encerrado en un bucle de redirecciones sin poder ni
        // cambiar la clave ni cerrar sesion.
        if ($request->routeIs('admin.password.change', 'admin.password.forced', 'admin.logout')) {
            return $next($request);
        }

        // Livewire actualiza en segundo plano: redirigir su peticion no lleva
        // a ninguna parte, solo rompe el componente. Se responde con un 409
        // para que el navegador recargue por su cuenta.
        if ($request->hasHeader('X-Livewire')) {
            return response('', Response::HTTP_CONFLICT);
        }

        return redirect()->route('admin.password.change');
    }
}
