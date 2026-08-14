<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Catalogo de configuracion del sitio.
 *
 * Los valores iniciales salen del diseno de Stitch y son FICTICIOS: correo,
 * telefono y direccion deben sustituirse antes de produccion.
 * Ver docs/12_KNOWN_ISSUES.md #11 y el checklist de docs/09_DEPLOYMENT.md.
 *
 * El seeder es idempotente: crea las claves que falten y respeta los valores
 * que el administrador ya haya cambiado.
 */
class SettingsSeeder extends Seeder
{
    /**
     * [key, type, group, is_public, is_translatable, is_encrypted, default]
     *
     * @return list<array{0:string,1:string,2:string,3:bool,4:bool,5:bool,6:mixed}>
     */
    public static function definitions(): array
    {
        return [
            // ---------------- General ----------------
            ['site_name', 'string', 'general', true, false, false, 'ERA Realty RD'],
            ['site_tagline', 'string', 'general', true, true, false, [
                'es' => 'Bienes raíces en República Dominicana',
                'en' => 'Real estate in the Dominican Republic',
            ]],
            ['site_logo', 'image', 'general', true, false, false, 'brand/era-mv-realty-logo-clean.webp'],
            ['site_logo_dark', 'image', 'general', true, false, false, 'brand/era-mv-realty-logo-light-clean.webp'],
            ['site_favicon', 'image', 'general', true, false, false, null],

            // ---------------- Contacto ----------------
            ['contact_phone', 'string', 'contact', true, false, false, '+1 (809) 555-0100'],
            ['contact_email', 'email', 'contact', true, false, false, 'info@erarealtyrd.com'],
            // Privado: es el buzon interno, no debe aparecer en el sitio.
            ['contact_form_recipient_email', 'email', 'contact', false, false, false, 'info@erarealtyrd.com'],
            ['contact_address', 'text', 'contact', true, false, false, 'Av. Winston Churchill, Santo Domingo, Rep. Dom.'],
            ['contact_schedule', 'string', 'contact', true, true, false, [
                'es' => 'Lunes a viernes, 9:00 a 18:00',
                'en' => 'Monday to Friday, 9:00 am – 6:00 pm',
            ]],
            ['contact_map_embed', 'text', 'contact', true, false, false, null],

            // ---------------- WhatsApp ----------------
            // El enlace no se guarda: lo genera WhatsappService.
            ['contact_whatsapp_number', 'string', 'whatsapp', true, false, false, '18095550100'],
            ['contact_whatsapp_message', 'text', 'whatsapp', true, true, false, [
                'es' => 'Hola, quiero recibir asesoría inmobiliaria.',
                'en' => 'Hello, I would like to receive real estate advice.',
            ]],
            ['whatsapp_property_message', 'text', 'whatsapp', true, true, false, [
                'es' => 'Hola, estoy interesado en la propiedad {reference_code} - {title}. ¿Está disponible?',
                'en' => 'Hello, I am interested in property {reference_code} - {title}. Is it still available?',
            ]],
            ['whatsapp_investment_message', 'text', 'whatsapp', true, true, false, [
                'es' => 'Hola, quiero información sobre oportunidades de inversión inmobiliaria.',
                'en' => 'Hello, I would like information about real estate investment opportunities.',
            ]],
            ['whatsapp_float_enabled', 'boolean', 'whatsapp', true, false, false, '1'],
            ['whatsapp_float_position', 'select', 'whatsapp', true, false, false, 'bottom-right'],

            // ---------------- Redes sociales ----------------
            ['social_facebook', 'url', 'social', true, false, false, null],
            ['social_instagram', 'url', 'social', true, false, false, null],
            ['social_youtube', 'url', 'social', true, false, false, null],
            ['social_tiktok', 'url', 'social', true, false, false, null],
            ['social_linkedin', 'url', 'social', true, false, false, null],

            // ---------------- Pie ----------------
            ['footer_text', 'text', 'footer', true, true, false, [
                'es' => 'Tu socio confiable en el mercado inmobiliario de lujo en República Dominicana.',
                'en' => 'Your trusted partner in the Dominican Republic luxury real estate market.',
            ]],
            ['footer_copyright', 'string', 'footer', true, true, false, [
                'es' => 'Todos los derechos reservados.',
                'en' => 'All rights reserved.',
            ]],

            // ---------------- SEO ----------------
            ['seo_default_title', 'string', 'seo', true, true, false, [
                'es' => 'ERA Realty RD | Propiedades en República Dominicana',
                'en' => 'ERA Realty RD | Properties in the Dominican Republic',
            ]],
            ['seo_default_description', 'text', 'seo', true, true, false, [
                'es' => 'Compra, alquila o invierte en propiedades verificadas en República Dominicana con asesoría profesional.',
                'en' => 'Buy, rent or invest in verified properties across the Dominican Republic with professional guidance.',
            ]],
            ['seo_default_og_image', 'image', 'seo', true, false, false, null],
            ['seo_google_analytics_id', 'string', 'seo', false, false, false, null],
            ['seo_google_site_verification', 'string', 'seo', false, false, false, null],
            ['seo_robots_txt', 'text', 'seo', false, false, false, null],

            // ---------------- Moneda ----------------
            // El sitio opera en USD y DOP. La tasa la mantiene el administrador.
            ['currency_default', 'select', 'currency', true, false, false, 'USD'],
            ['currency_usd_to_dop', 'decimal', 'currency', true, false, false, '60.50'],
            ['currency_rate_updated_at', 'string', 'currency', false, false, false, null],

            // ---------------- Correo (privado) ----------------
            ['mail_mailer', 'select', 'mail', false, false, false, 'smtp'],
            ['mail_host', 'string', 'mail', false, false, false, null],
            ['mail_port', 'integer', 'mail', false, false, false, '587'],
            ['mail_username', 'string', 'mail', false, false, false, null],
            ['mail_password', 'string', 'mail', false, false, true, null],
            ['mail_encryption', 'select', 'mail', false, false, false, 'tls'],
            ['mail_from_address', 'email', 'mail', false, false, false, 'no-reply@erarealtyrd.com'],
            ['mail_from_name', 'string', 'mail', false, false, false, 'ERA Realty RD'],
            ['mail_send_client_confirmation', 'boolean', 'mail', false, false, false, '0'],
        ];
    }

    public function run(): void
    {
        $creadas = 0;

        foreach (self::definitions() as [$key, $type, $group, $isPublic, $isTranslatable, $isEncrypted, $default]) {

            $existente = Setting::where('key', $key)->first();

            if ($existente) {
                // Se actualizan solo los metadatos, nunca el valor: el
                // administrador pudo haberlo cambiado ya.
                $existente->update([
                    'type' => $type,
                    'group' => $group,
                    'is_public' => $isPublic,
                    'is_translatable' => $isTranslatable,
                    'is_encrypted' => $isEncrypted,
                ]);

                continue;
            }

            Setting::create([
                'key' => $key,
                'value' => is_array($default)
                    ? json_encode($default, JSON_UNESCAPED_UNICODE)
                    : $default,
                'type' => $type,
                'group' => $group,
                'is_public' => $isPublic,
                'is_translatable' => $isTranslatable,
                'is_encrypted' => $isEncrypted,
            ]);

            $creadas++;
        }

        app(SettingsService::class)->flush();

        $this->command->info("Configuracion: {$creadas} claves creadas, "
            .count(self::definitions()).' en total.');
    }
}
