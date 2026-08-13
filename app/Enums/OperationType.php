<?php

namespace App\Enums;

enum OperationType: string
{
    case Sale = 'sale';
    case Rent = 'rent';
    case TemporaryRent = 'temporary_rent';
    case Investment = 'investment';

    public function label(): string
    {
        return __('property.operation.'.$this->value);
    }

    /** ¿El precio lleva periodo (por mes, por noche)? */
    public function hasPeriod(): bool
    {
        return in_array($this, [self::Rent, self::TemporaryRent], true);
    }

    public function defaultPeriod(): ?PricePeriod
    {
        return match ($this) {
            self::Rent => PricePeriod::Month,
            self::TemporaryRent => PricePeriod::Night,
            default => null,
        };
    }
}
