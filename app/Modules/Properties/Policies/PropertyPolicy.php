<?php

namespace App\Modules\Properties\Policies;

use App\Models\User;
use App\Modules\Properties\Models\Property;

/**
 * Los permisos dicen QUE puede hacer un rol; la policy dice SOBRE QUE.
 *
 * El rol `agent` tiene manage_properties, pero solo sobre las propiedades que
 * tiene asignadas. Ese filtro no lo puede hacer el permiso: tiene que mirar
 * la fila concreta.
 */
class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_properties');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->can('manage_properties') && $this->owns($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_properties');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('manage_properties') && $this->owns($user, $property);
    }

    /**
     * Borrar queda reservado a admin y super_admin.
     *
     * Ni el agente ni el editor lo hacen: retirar una ficha del mercado es una
     * decision del negocio, no de quien redacta o comercializa. El permiso
     * manage_properties habilita crear y editar, no destruir.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->delete($user, $property);
    }

    /** El borrado definitivo destruye tambien las imagenes del disco. */
    public function forceDelete(User $user, Property $property): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Publicar tampoco lo hace un agente: lo que sale al sitio publico lo
     * decide la inmobiliaria. Un editor si puede, porque es su trabajo.
     */
    public function publish(User $user, Property $property): bool
    {
        return $user->can('manage_properties')
            && $this->owns($user, $property)
            && ! $user->hasRole('agent');
    }

    /**
     * Un agente solo trabaja sus propiedades asignadas.
     * El resto de roles con permiso ven todas.
     */
    private function owns(User $user, Property $property): bool
    {
        if (! $user->hasRole('agent')) {
            return true;
        }

        $agentId = $user->agent?->id;

        return $agentId !== null && $property->agent_id === $agentId;
    }
}
