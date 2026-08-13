<?php

namespace App\Modules\Settings\Services;

use Illuminate\Support\Facades\Config;

/**
 * Configuracion SMTP editable desde el panel.
 *
 * Precedencia: si la configuracion de base de datos esta completa, gana sobre
 * el .env. Asi el administrador puede cambiar de proveedor sin acceso al
 * servidor, que es lo que pide el prompt maestro (§7).
 */
class MailConfigService
{
    /** Claves minimas para considerar la configuracion utilizable. */
    private const REQUIRED = ['mail_host', 'mail_port', 'mail_from_address'];

    public function __construct(private SettingsService $settings) {}

    public function isConfigured(): bool
    {
        foreach (self::REQUIRED as $key) {
            if (blank($this->settings->get($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vuelca la configuracion de base de datos sobre la de runtime.
     * Se llama desde un middleware antes de cualquier envio.
     */
    public function apply(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        Config::set($this->buildConfig());
    }

    /**
     * Configuracion equivalente a partir de valores sueltos, sin tocar la
     * configuracion activa. La usa el envio de prueba: se verifica ANTES de
     * guardar, para no dejar guardadas unas credenciales que no funcionan.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function buildConfig(array $values = []): array
    {
        $get = fn (string $key, mixed $default = null) => $values[$key]
            ?? $this->settings->get($key, $default);

        $mailer = $get('mail_mailer', 'smtp');

        return [
            'mail.default' => $mailer,
            "mail.mailers.{$mailer}.transport" => $mailer,
            "mail.mailers.{$mailer}.host" => $get('mail_host'),
            "mail.mailers.{$mailer}.port" => (int) $get('mail_port', 587),
            "mail.mailers.{$mailer}.username" => $get('mail_username'),
            "mail.mailers.{$mailer}.password" => $get('mail_password'),
            "mail.mailers.{$mailer}.encryption" => $get('mail_encryption', 'tls'),
            'mail.from.address' => $get('mail_from_address'),
            'mail.from.name' => $get('mail_from_name', $this->settings->get('site_name')),
        ];
    }

    /**
     * Destinatario de los formularios. Si no esta configurado, cae al correo
     * de contacto publico: es preferible que un lead llegue al buzon general
     * a que se pierda.
     */
    public function formRecipient(): ?string
    {
        return $this->settings->get('contact_form_recipient_email')
            ?: $this->settings->get('contact_email');
    }
}
