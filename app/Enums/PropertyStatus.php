<?php

namespace App\Enums;

/**
 * Estados de una propiedad (prompt maestro §8).
 *
 * Solo `available` con published_at en el pasado sale al sitio publico.
 * Cualquier otro estado implica noindex y ausencia del sitemap.
 */
enum PropertyStatus: string
{
    case Draft = 'draft';
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Rented = 'rented';
    case NotAvailable = 'not_available';
    case Paused = 'paused';

    public function label(): string
    {
        return __('property.status.'.$this->value);
    }

    /** Color del chip, segun DESIGN.md. */
    public function color(): string
    {
        return match ($this) {
            self::Available => 'status-available',
            self::Sold => 'status-sold',
            self::Reserved => 'status-reserved',
            self::Rented => 'status-rented',
            default => 'status-unavailable',
        };
    }

    /** ¿Es visible en el sitio publico? */
    public function isPublic(): bool
    {
        // Vendida y alquilada SI se muestran: el diseno las incluye con su
        // chip, y sirven de prueba social del trabajo de la inmobiliaria.
        return in_array($this, [
            self::Available,
            self::Reserved,
            self::Sold,
            self::Rented,
        ], true);
    }

    /** ¿Se puede contactar por ella? */
    public function acceptsLeads(): bool
    {
        return in_array($this, [self::Available, self::Reserved], true);
    }

    /** ¿Debe indexarla Google? */
    public function isIndexable(): bool
    {
        return $this === self::Available || $this === self::Reserved;
    }

    /** @return list<self> */
    public static function publicCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isPublic()));
    }
}
