<?php

namespace App\Modules\Settings\Requests;

use App\Modules\WhatsApp\Services\WhatsappService;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WhatsappSettingsRequest extends FormRequest
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
            'contact_whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_float_enabled' => ['nullable', 'boolean'],
            'whatsapp_float_position' => ['required', 'in:bottom-right,bottom-left'],
        ];

        foreach (Locale::codes() as $code) {
            $rules["contact_whatsapp_message.{$code}"] = ['nullable', 'string', 'max:500'];
            $rules["whatsapp_property_message.{$code}"] = ['nullable', 'string', 'max:500'];
            $rules["whatsapp_investment_message.{$code}"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $numero = $this->input('contact_whatsapp_number');

            if (blank($numero)) {
                return;
            }

            $normalizado = app(WhatsappService::class)->normalize($numero);

            if ($normalizado === null || strlen($normalizado) < 10) {
                $validator->errors()->add(
                    'contact_whatsapp_number',
                    'El número no parece válido. Escribe al menos 10 dígitos, por ejemplo (809) 555-0100.'
                );
            }
        });
    }
}
