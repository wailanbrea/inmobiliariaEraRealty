<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Mail\TestMail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envio de correo de prueba.
 *
 * Se prueba con los valores del formulario ANTES de guardarlos. Si el envio
 * falla, no se guarda nada. Esto evita el escenario tipico de guardar unas
 * credenciales mal escritas y descubrirlo semanas despues por leads perdidos.
 */
class MailTestService
{
    public function __construct(private MailConfigService $mailConfig) {}

    /**
     * @param  array<string, mixed>  $values  configuracion a probar
     * @return array{ok: bool, message: string}
     */
    public function send(string $recipient, array $values = []): array
    {
        $original = Config::get('mail');

        try {
            Config::set($this->mailConfig->buildConfig($values));

            // El transporte se reconstruye a partir de la configuracion nueva.
            Mail::purge(Config::get('mail.default'));

            Mail::to($recipient)->send(new TestMail);

            return [
                'ok' => true,
                'message' => __('admin/settings.mail.test_ok', ['email' => $recipient]),
            ];
        } catch (\Throwable $e) {
            Log::warning('Fallo el correo de prueba', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                // Se muestra el error real de SMTP ("535 Auth failed",
                // "Connection refused"): sin el, el administrador no tiene
                // forma de saber que corregir.
                'message' => $e->getMessage(),
            ];
        } finally {
            Config::set('mail', $original);
            Mail::purge(Config::get('mail.default'));
        }
    }
}
