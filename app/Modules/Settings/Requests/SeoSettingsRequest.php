<?php

namespace App\Modules\Settings\Requests;

use App\Rules\RealImage;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;

class SeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_seo') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'seo_default_og_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', new RealImage(allowSvg: false)],
            'seo_google_analytics_id' => ['nullable', 'string', 'max:50', 'regex:/^(G-|UA-|GTM-)[A-Z0-9\-]+$/i'],
            'seo_google_site_verification' => ['nullable', 'string', 'max:190'],
            'seo_robots_txt' => ['nullable', 'string', 'max:5000'],
        ];

        foreach (Locale::codes() as $code) {
            // 60 y 155 son limites recomendados, no duros: Google los recorta
            // pero no penaliza. Se avisa en pantalla y se permite algo mas.
            $rules["seo_default_title.{$code}"] = ['nullable', 'string', 'max:120'];
            $rules["seo_default_description.{$code}"] = ['nullable', 'string', 'max:300'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seo_google_analytics_id.regex' => 'El ID debe tener el formato G-XXXXXXX, UA-XXXXXX-X o GTM-XXXXXXX.',
        ];
    }
}
