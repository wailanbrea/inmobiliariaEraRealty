<?php

namespace App\Modules\WhatsApp\Services;

use App\Modules\Settings\Services\SettingsService;

/**
 * Generacion de enlaces wa.me.
 *
 * El enlace NUNCA se almacena: se deriva siempre del numero y del mensaje.
 * Guardarlo (como sugeria el prompt maestro con contact_whatsapp_link) crearia
 * un tercer dato que se desincroniza en cuanto alguien cambie el numero y
 * olvide regenerarlo. Ver docs/06_EMAIL_AND_WHATSAPP.md seccion 5.
 */
class WhatsappService
{
    /** Prefijos de la Republica Dominicana. */
    private const RD_AREA_CODES = ['809', '829', '849'];

    public function __construct(private SettingsService $settings) {}

    /**
     * Deja el numero en el formato que espera wa.me: solo digitos, con codigo
     * de pais.
     *
     *   (809) 555-0100   -> 18095550100
     *   809-555-0100     -> 18095550100
     *   +1 809 555 0100  -> 18095550100
     *   8295550100       -> 18295550100
     */
    public function normalize(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (blank($digits)) {
            return null;
        }

        // 10 digitos empezando por un prefijo dominicano: falta el 1 del pais.
        if (strlen($digits) === 10 && in_array(substr($digits, 0, 3), self::RD_AREA_CODES, true)) {
            return '1'.$digits;
        }

        // Cualquier otro caso se respeta tal cual: puede ser un numero
        // extranjero perfectamente valido.
        return $digits;
    }

    /**
     * Enlace completo listo para un href.
     */
    public function link(?string $number = null, ?string $message = null): ?string
    {
        $number = $this->normalize($number ?? $this->settings->get('contact_whatsapp_number'));

        if ($number === null) {
            return null;
        }

        $url = "https://wa.me/{$number}";

        if (filled($message)) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /**
     * Mensaje general (boton flotante, header, contacto).
     */
    public function generalMessage(): ?string
    {
        return $this->settings->get('contact_whatsapp_message');
    }

    /**
     * Mensaje de la pagina Invierte.
     */
    public function investmentMessage(): ?string
    {
        return $this->settings->get('whatsapp_investment_message');
    }

    /**
     * Mensaje de una propiedad, con las variables sustituidas.
     *
     * Variables admitidas: {reference_code} {title} {price} {location} {url}
     *
     * @param  array<string, string|null>  $replacements
     */
    public function propertyMessage(array $replacements): ?string
    {
        $template = $this->settings->get('whatsapp_property_message');

        if (blank($template)) {
            return null;
        }

        return $this->interpolate($template, $replacements);
    }

    /**
     * @param  array<string, string|null>  $replacements
     */
    public function interpolate(string $template, array $replacements): string
    {
        $search = [];
        $replace = [];

        foreach ($replacements as $key => $value) {
            $search[] = '{'.$key.'}';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $template);
    }

    /**
     * Enlace general listo para usar, o null si no hay numero configurado.
     */
    public function generalLink(): ?string
    {
        return $this->link(null, $this->generalMessage());
    }

    public function isFloatEnabled(): bool
    {
        return (bool) $this->settings->get('whatsapp_float_enabled', false)
            && $this->generalLink() !== null;
    }

    public function floatPosition(): string
    {
        $position = $this->settings->get('whatsapp_float_position', 'bottom-right');

        return in_array($position, ['bottom-right', 'bottom-left'], true)
            ? $position
            : 'bottom-right';
    }

    /**
     * Version legible del numero para mostrarlo en pantalla.
     * 18095550100 -> +1 (809) 555-0100
     */
    public function formatForDisplay(?string $number): ?string
    {
        $digits = $this->normalize($number);

        if ($digits === null) {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return sprintf(
                '+1 (%s) %s-%s',
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 4)
            );
        }

        return '+'.$digits;
    }
}
