<?php

namespace App\Enums;

/**
 * El sitio opera en dolares y pesos dominicanos a la vez.
 * La tasa la mantiene el administrador en la configuracion.
 */
enum Currency: string
{
    case USD = 'USD';
    case DOP = 'DOP';

    public function symbol(): string
    {
        return match ($this) {
            self::USD => 'US$',
            self::DOP => 'RD$',
        };
    }

    public function label(): string
    {
        return __('property.currency.'.$this->value);
    }

    /**
     * Formatea un importe en esta moneda.
     * Sin decimales: en inmobiliaria los centavos son ruido.
     */
    public function format(int|float|null $amount): string
    {
        if ($amount === null) {
            return __('property.price_on_request');
        }

        return $this->symbol().' '.number_format((float) $amount, 0, '.', ',');
    }

    /**
     * Convierte a la otra moneda usando la tasa configurada.
     * Devuelve null si no hay tasa utilizable, para que la vista no muestre
     * una conversion inventada.
     */
    public function convertTo(self $target, int|float|null $amount): ?float
    {
        if ($amount === null || $this === $target) {
            return $amount === null ? null : (float) $amount;
        }

        $rate = (float) setting('currency_usd_to_dop', 0);

        if ($rate <= 0) {
            return null;
        }

        return $this === self::USD
            ? (float) $amount * $rate
            : (float) $amount / $rate;
    }
}
