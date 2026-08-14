<?php

use App\Modules\Leads\Mail\LeadConfirmationMail;
use App\Modules\Leads\Mail\NewLeadNotificationMail;
use App\Modules\Leads\Models\Lead;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Mail::fake();
});

function confirmationLeadPayload(?string $email = 'cliente@example.com'): array
{
    return [
        'name' => 'Cliente Confirmacion', 'phone' => '8095550101', 'email' => $email,
        'subject' => 'Consulta', 'interest_type' => 'buy',
        'message' => 'Quiero recibir informacion sobre propiedades.',
        'form_token' => Crypt::encryptString((string) now()->subSeconds(4)->timestamp),
    ];
}

it('envia confirmacion cuando esta activa y hay correo', function () {
    app(SettingsService::class)->setMany(['mail_send_client_confirmation' => true]);

    $this->post('/contactanos', confirmationLeadPayload())->assertRedirect('/contactanos');

    $lead = Lead::firstOrFail();
    Mail::assertQueued(NewLeadNotificationMail::class);
    Mail::assertQueued(LeadConfirmationMail::class, fn ($mail) => $mail->lead->is($lead));
});

it('no envia confirmacion cuando esta desactivada', function () {
    $this->post('/contactanos', confirmationLeadPayload())->assertRedirect('/contactanos');

    Mail::assertQueued(NewLeadNotificationMail::class);
    Mail::assertNotQueued(LeadConfirmationMail::class);
});

it('no intenta confirmar si el cliente no dio correo', function () {
    app(SettingsService::class)->setMany(['mail_send_client_confirmation' => true]);

    $this->post('/contactanos', confirmationLeadPayload(null))->assertRedirect('/contactanos');

    expect(Lead::count())->toBe(1);
    Mail::assertNotQueued(LeadConfirmationMail::class);
});
