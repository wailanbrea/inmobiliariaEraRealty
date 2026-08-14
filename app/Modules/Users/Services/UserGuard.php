<?php

namespace App\Modules\Users\Services;

use App\Models\User;

/**
 * Reglas que impiden que el panel se quede sin dueno.
 *
 * Viven aqui y no en el componente Livewire porque las comparte el comando de
 * consola. Una invariante duplicada en dos sitios es una invariante que algun
 * dia solo se cumple en uno.
 *
 * El escenario que evitan es concreto y ha pasado en muchos proyectos: alguien
 * se quita a si mismo el rol para "probar como lo ve un editor", o desactiva
 * la cuenta del companero que se fue, y resulta que era el ultimo con acceso
 * total. Sin correo configurado, eso deja el panel cerrado para siempre.
 */
class UserGuard
{
    public const ROLES = ['super_admin', 'admin', 'editor', 'agent'];

    /**
     * Numero de super administradores ACTIVOS, que son los unicos que cuentan:
     * uno desactivado no puede entrar a arreglar nada.
     */
    public function activeSuperAdmins(?int $exceptoId = null): int
    {
        return User::role('super_admin')
            ->where('is_active', true)
            ->when($exceptoId, fn ($q) => $q->whereKeyNot($exceptoId))
            ->count();
    }

    /**
     * ¿Es el unico super administrador activo que queda?
     *
     * Se pregunta a la base de datos en vez de leer $user->is_active.
     *
     * Un modelo recien construido puede no tener ese atributo cargado —el
     * valor lo pone el DEFAULT de la tabla, no la instancia—, y entonces la
     * comprobacion daria false y dejaria pasar justo la operacion que tenia
     * que bloquear. Preguntar por el estado real cuesta una consulta y no
     * depende de como llego el modelo hasta aqui.
     */
    public function isLastSuperAdmin(User $user): bool
    {
        $estaActivo = User::whereKey($user->getKey())
            ->where('is_active', true)
            ->exists();

        return $user->isSuperAdmin()
            && $estaActivo
            && $this->activeSuperAdmins($user->getKey()) === 0;
    }

    /**
     * Motivo por el que NO se puede desactivar, o null si se puede.
     */
    public function cannotDeactivate(User $user, ?User $actor = null): ?string
    {
        if ($actor && $actor->is($user)) {
            return __('admin/users.errors.self_deactivate');
        }

        if ($this->isLastSuperAdmin($user)) {
            return __('admin/users.errors.last_super_admin');
        }

        return null;
    }

    /**
     * Motivo por el que NO se puede cambiar el rol, o null si se puede.
     */
    public function cannotChangeRole(User $user, string $nuevoRol, ?User $actor = null): ?string
    {
        // Quitarse a uno mismo el rol maximo es el camino mas corto a quedarse
        // fuera, y ademas nunca es lo que se pretende: para eso se crea otra
        // cuenta con menos permisos.
        if ($actor && $actor->is($user) && $user->isSuperAdmin() && $nuevoRol !== 'super_admin') {
            return __('admin/users.errors.self_demote');
        }

        if ($nuevoRol !== 'super_admin' && $this->isLastSuperAdmin($user)) {
            return __('admin/users.errors.last_super_admin');
        }

        return null;
    }

    /**
     * Motivo por el que NO se puede borrar, o null si se puede.
     */
    public function cannotDelete(User $user, ?User $actor = null): ?string
    {
        if ($actor && $actor->is($user)) {
            return __('admin/users.errors.self_delete');
        }

        if ($user->isSuperAdmin() && $this->activeSuperAdmins($user->id) === 0) {
            return __('admin/users.errors.last_super_admin');
        }

        return null;
    }
}
