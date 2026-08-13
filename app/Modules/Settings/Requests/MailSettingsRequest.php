<?php

namespace App\Modules\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MailSettingsRequest extends FormRequest
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
        return [
            'mail_mailer' => ['required', 'in:smtp,sendmail,log,array'],
            'mail_host' => ['nullable', 'string', 'max:190'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:190'],
            // Vacia = conservar la actual. Nunca se devuelve al formulario.
            'mail_password' => ['nullable', 'string', 'max:190'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['required', 'string', 'max:150'],
            'mail_send_client_confirmation' => ['nullable', 'boolean'],

            'test_recipient' => ['required_with:send_test', 'nullable', 'email', 'max:190'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_from_address.required' => 'El correo remitente es obligatorio.',
            'mail_from_name.required' => 'El nombre del remitente es obligatorio.',
            'test_recipient.required_with' => 'Indica a qué dirección enviar la prueba.',
        ];
    }
}
