<?php

use App\Modules\WhatsApp\Models\WhatsappClick;

it('registra un clic publico con contexto del servidor', function () {
    $this->withHeaders([
        'User-Agent' => 'Mobile Browser',
        'Referer' => 'https://example.test/invierte',
    ])->postJson('/wa/click', [
        'source' => 'investment_page',
        'phone_number' => '18095550100',
        'generated_message' => 'Quiero invertir',
    ])->assertNoContent();

    $click = WhatsappClick::firstOrFail();
    expect($click->source)->toBe('investment_page')
        ->and($click->phone_number)->toBe('18095550100')
        ->and($click->user_agent)->toBe('Mobile Browser')
        ->and($click->referrer_url)->toBe('https://example.test/invierte')
        ->and($click->ip_address)->not->toBeNull();
});

it('rechaza datos manipulados', function () {
    $this->postJson('/wa/click', [
        'source' => '<script>',
        'phone_number' => 'no-es-numero',
        'generated_message' => str_repeat('x', 2001),
    ])->assertUnprocessable()->assertJsonValidationErrors(['source', 'phone_number', 'generated_message']);

    expect(WhatsappClick::count())->toBe(0);
});

it('publica la configuracion de captura en el layout', function () {
    $this->get('/')->assertOk()
        ->assertSee('name="csrf-token"', false)
        ->assertSee(route('whatsapp.click'), false);
});
