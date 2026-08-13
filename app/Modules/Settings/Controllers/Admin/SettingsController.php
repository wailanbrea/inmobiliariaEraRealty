<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Requests\GeneralSettingsRequest;
use App\Modules\Settings\Requests\MailSettingsRequest;
use App\Modules\Settings\Requests\SeoSettingsRequest;
use App\Modules\Settings\Requests\WhatsappSettingsRequest;
use App\Modules\Settings\Services\MailTestService;
use App\Modules\Settings\Services\SettingsImageService;
use App\Modules\Settings\Services\SettingsService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** Claves traducibles por pestana. */
    private const TRANSLATABLE = [
        'general' => ['site_tagline', 'contact_schedule', 'footer_text', 'footer_copyright'],
        'whatsapp' => ['contact_whatsapp_message', 'whatsapp_property_message', 'whatsapp_investment_message'],
        'seo' => ['seo_default_title', 'seo_default_description'],
    ];

    private const IMAGES = [
        'general' => ['site_logo', 'site_logo_dark', 'site_favicon'],
        'seo' => ['seo_default_og_image'],
    ];

    public function __construct(
        private SettingsService $settings,
        private SettingsImageService $images,
    ) {}

    // ------------------------------------------------------------------
    // General
    // ------------------------------------------------------------------

    public function general(): View
    {
        return view('admin.settings.general', $this->payload('general', [
            'site_name', 'contact_phone', 'contact_email',
            'contact_form_recipient_email', 'contact_address',
            'social_facebook', 'social_instagram', 'social_youtube',
            'social_tiktok', 'social_linkedin',
            'currency_default', 'currency_usd_to_dop',
        ]));
    }

    public function updateGeneral(GeneralSettingsRequest $request): RedirectResponse
    {
        $this->storeImages($request, self::IMAGES['general']);

        $this->settings->setMany(array_merge(
            $request->safe()->only([
                'site_name', 'contact_phone', 'contact_email',
                'contact_form_recipient_email', 'contact_address',
                'social_facebook', 'social_instagram', 'social_youtube',
                'social_tiktok', 'social_linkedin',
                'currency_default', 'currency_usd_to_dop',
            ]),
            $this->translatable($request, self::TRANSLATABLE['general']),
        ));

        if ($request->filled('currency_usd_to_dop')) {
            $this->settings->set('currency_rate_updated_at', now()->toDateTimeString());
        }

        return back()->with('status', __('admin/settings.saved'));
    }

    // ------------------------------------------------------------------
    // WhatsApp
    // ------------------------------------------------------------------

    public function whatsapp(): View
    {
        return view('admin.settings.whatsapp', $this->payload('whatsapp', [
            'contact_whatsapp_number', 'whatsapp_float_enabled', 'whatsapp_float_position',
        ]));
    }

    public function updateWhatsapp(WhatsappSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany(array_merge([
            'contact_whatsapp_number' => $request->input('contact_whatsapp_number'),
            'whatsapp_float_enabled' => $request->boolean('whatsapp_float_enabled'),
            'whatsapp_float_position' => $request->input('whatsapp_float_position'),
        ], $this->translatable($request, self::TRANSLATABLE['whatsapp'])));

        return back()->with('status', __('admin/settings.saved'));
    }

    // ------------------------------------------------------------------
    // Correo
    // ------------------------------------------------------------------

    public function mail(): View
    {
        return view('admin.settings.mail', $this->payload('mail', [
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
            'mail_send_client_confirmation',
        ]) + [
            // Nunca se devuelve la contrasena al formulario; solo si existe.
            'hasPassword' => filled($this->settings->get('mail_password')),
        ]);
    }

    /**
     * Guarda la configuracion de correo.
     *
     * Si se pide prueba, se envia ANTES de guardar con los valores del
     * formulario. Si falla, no se guarda nada: asi no quedan activas unas
     * credenciales que no funcionan. Lo exige el prompt maestro (§7).
     */
    public function updateMail(MailSettingsRequest $request, MailTestService $tester): RedirectResponse
    {
        $values = $request->safe()->only([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
        ]);

        $values['mail_send_client_confirmation'] = $request->boolean('mail_send_client_confirmation');

        // Contrasena vacia = conservar la actual.
        $password = $request->filled('mail_password')
            ? $request->input('mail_password')
            : $this->settings->get('mail_password');

        if ($request->boolean('send_test')) {
            $resultado = $tester->send(
                $request->input('test_recipient'),
                $values + ['mail_password' => $password],
            );

            if (! $resultado['ok']) {
                return back()
                    ->withInput($request->except('mail_password'))
                    ->with('mail_test_error', $resultado['message'])
                    ->withErrors(['mail_test' => __('admin/settings.mail.test_failed')]);
            }

            $this->persistMail($values, $request, $password);

            return back()->with('status', $resultado['message']);
        }

        $this->persistMail($values, $request, $password);

        return back()->with('status', __('admin/settings.saved'));
    }

    // ------------------------------------------------------------------
    // SEO
    // ------------------------------------------------------------------

    public function seo(): View
    {
        return view('admin.settings.seo', $this->payload('seo', [
            'seo_google_analytics_id', 'seo_google_site_verification', 'seo_robots_txt',
        ]));
    }

    public function updateSeo(SeoSettingsRequest $request): RedirectResponse
    {
        $this->storeImages($request, self::IMAGES['seo']);

        $this->settings->setMany(array_merge(
            $request->safe()->only([
                'seo_google_analytics_id', 'seo_google_site_verification', 'seo_robots_txt',
            ]),
            $this->translatable($request, self::TRANSLATABLE['seo']),
        ));

        return back()->with('status', __('admin/settings.saved'));
    }

    // ------------------------------------------------------------------
    // Imagenes
    // ------------------------------------------------------------------

    public function removeImage(Request $request, string $key): RedirectResponse
    {
        abort_unless($request->user()->can('manage_settings'), 403);
        abort_unless(in_array($key, array_merge(...array_values(self::IMAGES)), true), 404);

        $this->images->remove($key);

        return back()->with('status', __('admin/settings.saved'));
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * @param  list<string>  $plain
     * @return array<string, mixed>
     */
    private function payload(string $tab, array $plain): array
    {
        $values = [];

        foreach ($plain as $key) {
            $values[$key] = $this->settings->get($key);
        }

        $translations = [];

        foreach (self::TRANSLATABLE[$tab] ?? [] as $key) {
            $translations[$key] = $this->settings->translations($key);
        }

        $images = [];

        foreach (self::IMAGES[$tab] ?? [] as $key) {
            $images[$key] = $this->settings->get($key);
        }

        return [
            'tab' => $tab,
            'values' => $values,
            'translations' => $translations,
            'images' => $images,
            'locales' => Locale::supported(),
        ];
    }

    /**
     * Recoge los campos traducibles del formulario en forma {es: ..., en: ...}
     *
     * @param  list<string>  $keys
     * @return array<string, array<string, string|null>>
     */
    private function translatable(Request $request, array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            $valores = $request->input($key, []);

            $out[$key] = collect(Locale::codes())
                ->mapWithKeys(fn (string $code) => [$code => $valores[$code] ?? null])
                ->all();
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     */
    private function storeImages(Request $request, array $keys): void
    {
        foreach ($keys as $key) {
            if ($request->hasFile($key)) {
                $this->images->store($key, $request->file($key));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persistMail(array $values, Request $request, ?string $password): void
    {
        $this->settings->setMany($values + ['mail_password' => $password]);
    }
}
