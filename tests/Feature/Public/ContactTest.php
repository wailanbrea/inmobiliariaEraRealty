<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Modules\Leads\Mail\NewLeadNotificationMail;
use App\Modules\Leads\Models\Lead;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

function validFormToken(): string
{
    return Crypt::encryptString((string) now()->subSeconds(4)->timestamp);
}

it('muestra contacto en ambos idiomas', function () {
    $this->get('/contactanos')->assertOk();
    $this->get('/en/contact')->assertOk()->assertSee('Tell us what you need', false);
});

it('guarda una consulta antes de encolar su correo', function () {
    Mail::fake();
    $this->post('/contactanos', [
        'name' => 'Ana Perez', 'phone' => '(809) 555-0101', 'email' => 'ana@example.com',
        'subject' => 'Busco apartamento', 'interest_type' => 'buy',
        'preferred_contact' => 'whatsapp', 'message' => 'Quiero comprar en Santo Domingo.',
        'form_token' => validFormToken(), 'website' => '',
    ])->assertRedirect('/contactanos');

    $lead = Lead::firstOrFail();
    expect($lead->source)->toBe(LeadSource::ContactPage)
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->details['subject'])->toBe('Busco apartamento');
    Mail::assertQueued(NewLeadNotificationMail::class, fn ($mail) => $mail->lead->is($lead));
});

it('conserva el lead aunque falle el envio del correo', function () {
    // Es la garantia central del modulo (docs/06 y docs/08): si el SMTP esta
    // mal configurado o cae, el contacto NO se pierde. Se guarda igual, el
    // fallo se registra, y el administrador lo ve en el panel.
    Mail::shouldReceive('to->queue')->andThrow(new RuntimeException('SMTP caido'));

    $respuesta = $this->post('/contactanos', [
        'name' => 'Ana Perez', 'phone' => '(809) 555-0101', 'email' => 'ana@example.com',
        'subject' => 'Busco apartamento', 'interest_type' => 'buy',
        'preferred_contact' => 'whatsapp', 'message' => 'Quiero comprar en Santo Domingo.',
        'form_token' => validFormToken(), 'website' => '',
    ]);

    // Al visitante no se le muestra un error: su mensaje si llego.
    $respuesta->assertRedirect('/contactanos');

    expect(Lead::count())->toBe(1)
        ->and(Lead::first()->status)->toBe(LeadStatus::New)
        ->and(Lead::first()->name)->toBe('Ana Perez');
});

it('valida los datos del contacto', function () {
    $this->from('/contactanos')->post('/contactanos', [
        'name' => '', 'phone' => 'abc', 'form_token' => validFormToken(),
    ])->assertRedirect('/contactanos')->assertSessionHasErrors(['name', 'phone', 'subject', 'interest_type', 'message']);
    expect(Lead::count())->toBe(0);
});

it('marca como spam el honeypot y no envia correo', function () {
    Mail::fake();
    $this->post('/contactanos', [
        'name' => 'Bot', 'phone' => '8095550101', 'subject' => 'Spam',
        'interest_type' => 'other', 'message' => 'Mensaje automatico largo.',
        'form_token' => validFormToken(), 'website' => 'https://spam.test',
    ])->assertRedirect('/contactanos');
    expect(Lead::firstOrFail()->status)->toBe(LeadStatus::Spam);
    Mail::assertNothingQueued();
});

it('marca como spam un envio demasiado rapido', function () {
    $this->post('/contactanos', [
        'name' => 'Bot rapido', 'phone' => '8095550101', 'subject' => 'Spam',
        'interest_type' => 'other', 'message' => 'Mensaje automatico largo.',
        'form_token' => Crypt::encryptString((string) now()->timestamp),
    ])->assertRedirect('/contactanos');
    expect(Lead::firstOrFail()->status)->toBe(LeadStatus::Spam);
});
