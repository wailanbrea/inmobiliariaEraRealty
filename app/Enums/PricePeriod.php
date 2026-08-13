<?php

namespace App\Enums;

enum PricePeriod: string
{
    case Month = 'month';
    case Night = 'night';
    case Year = 'year';

    public function label(): string
    {
        return __('property.period.'.$this->value);
    }

    /** Sufijo corto para pegar al precio: "$1,200 /mes" */
    public function suffix(): string
    {
        return __('property.period_short.'.$this->value);
    }
}
