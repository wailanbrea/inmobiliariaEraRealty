<?php

namespace App\Modules\Audit\Observers;

use App\Enums\AuditAction;
use App\Modules\Audit\Services\AuditService;
use App\Modules\PropertyImages\Models\PropertyImage;

/**
 * Auditoria de imagenes.
 *
 * Solo alta y baja. Las ediciones —reordenar, marcar principal, cambiar el
 * texto alternativo— se dejan fuera a proposito: son decenas de escrituras
 * por sesion de trabajo y ahogarian el listado, que existe para responder
 * "quien borro esto" y no "en que orden quedaron las fotos".
 */
class PropertyImageObserver
{
    public function __construct(private AuditService $audit) {}

    public function created(PropertyImage $image): void
    {
        $this->audit->log(
            AuditAction::ImageUploaded,
            $image,
            new: ['path' => $image->path, 'property_id' => $image->property_id],
            label: $this->label($image),
        );
    }

    public function deleted(PropertyImage $image): void
    {
        $this->audit->log(
            AuditAction::ImageDeleted,
            $image,
            old: ['path' => $image->path, 'property_id' => $image->property_id],
            label: $this->label($image),
        );
    }

    /**
     * Se nombra por la propiedad a la que pertenece: "foto_1.webp" no le dice
     * nada a nadie tres meses despues.
     */
    private function label(PropertyImage $image): string
    {
        $property = $image->property;

        return $property?->title
            ? $property->title.' — '.$image->original_name
            : (string) $image->original_name;
    }
}
