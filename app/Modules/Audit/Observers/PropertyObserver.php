<?php

namespace App\Modules\Audit\Observers;

use App\Enums\AuditAction;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Properties\Models\Property;

/**
 * Auditoria de propiedades.
 *
 * Va en un observer y no en el controlador para que quede registrado venga de
 * donde venga la escritura: el formulario del panel, el componente Livewire
 * del listado, un comando de consola o cualquier cosa futura. Un enganche en
 * el controlador solo cubre el camino que hoy pasa por el controlador.
 */
class PropertyObserver
{
    public function __construct(private AuditService $audit) {}

    public function created(Property $property): void
    {
        $this->audit->log(
            AuditAction::PropertyCreated,
            $property,
            label: $this->label($property),
        );
    }

    public function updated(Property $property): void
    {
        // El cambio de estado tambien es un 'updated', pero merece su propia
        // accion: pasar una villa a "vendida" es una decision de negocio, no
        // una edicion mas. Se emite UN solo apunte, con la accion que mejor
        // describe lo ocurrido, en vez de dos por el mismo guardado.
        $action = array_key_exists('status', $property->getChanges())
            ? AuditAction::PropertyStatusChanged
            : AuditAction::PropertyUpdated;

        $this->audit->logModelChange($action, $property, $this->label($property));
    }

    /**
     * Cubre el borrado suave. El definitivo pasa por forceDeleted, que aqui
     * no se observa: si algun dia se anade, tendra su propia accion.
     */
    public function deleted(Property $property): void
    {
        $this->audit->log(
            AuditAction::PropertyDeleted,
            $property,
            old: ['status' => $property->status?->value, 'published_at' => (string) $property->published_at],
            label: $this->label($property),
        );
    }

    /**
     * El titulo vive en las traducciones, asi que no basta con leer un campo
     * del modelo. Si la propiedad no tiene ninguna, se cae al identificador.
     */
    private function label(Property $property): string
    {
        return $property->title ?: 'Propiedad #'.$property->getKey();
    }
}
