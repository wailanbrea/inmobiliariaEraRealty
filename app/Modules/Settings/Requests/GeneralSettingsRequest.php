<?php

namespace App\Modules\Settings\Requests;

use App\Rules\RealImage;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'site_name' => ['required', 'string', 'max:150'],

            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_form_recipient_email' => ['nullable', 'email', 'max:190'],
            'contact_address' => ['nullable', 'string', 'max:255'],

            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_tiktok' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],

            'currency_default' => ['required', 'in:USD,DOP'],
            'currency_usd_to_dop' => ['required', 'numeric', 'min:1', 'max:1000'],

            // Capa 1-2: extension y tamano. Capa 3: RealImage mira el contenido.
            // Capa 4 (reescritura) la aplica SettingsImageService.
            // Ver docs/05_MEDIA_UPLOADS.md seccion 2.
            'site_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048', new RealImage],
            'site_logo_dark' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048', new RealImage],
            'site_favicon' => ['nullable', 'file', 'mimes:png,webp,svg', 'max:1024', new RealImage(minSize: 96)],
        ];

        // Campos traducibles: uno por idioma.
        foreach (Locale::codes() as $code) {
            $rules["site_tagline.{$code}"] = ['nullable', 'string', 'max:200'];
            $rules["contact_schedule.{$code}"] = ['nullable', 'string', 'max:150'];
            $rules["footer_text.{$code}"] = ['nullable', 'string', 'max:500'];
            $rules["footer_copyright.{$code}"] = ['nullable', 'string', 'max:200'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'El nombre de la inmobiliaria es obligatorio.',
            'currency_usd_to_dop.required' => 'La tasa de cambio es obligatoria: sin ella no se pueden convertir los precios.',
            'currency_usd_to_dop.numeric' => 'La tasa de cambio debe ser un número.',
            'site_favicon.dimensions' => 'El favicon debe medir al menos 96×96 píxeles.',
            'contact_form_recipient_email.email' => 'Este correo recibe los leads: debe ser una dirección válida.',
        ];
    }
}
