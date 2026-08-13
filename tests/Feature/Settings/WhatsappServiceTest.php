<?php

use App\Modules\Settings\Services\SettingsService;
use App\Modules\WhatsApp\Services\WhatsappService;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->settings = app(SettingsService::class);
    $this->settings->flush();
    $this->wa = app(WhatsappService::class);
});

/*
|--------------------------------------------------------------------------
| Normalizacion del numero
| Tabla de casos de docs/08_TESTING.md
|--------------------------------------------------------------------------
*/

it('normaliza el numero a formato internacional', function (?string $entrada, ?string $esperado) {
    expect($this->wa->normalize($entrada))->toBe($esperado);
})->with([
    'con parentesis' => ['(809) 555-0100', '18095550100'],
    'con guiones' => ['809-555-0100', '18095550100'],
    'con prefijo y espacios' => ['+1 809 555 0100', '18095550100'],
    'prefijo 829' => ['8295550100', '18295550100'],
    'prefijo 849' => ['8495550100', '18495550100'],
    'ya completo' => ['18095550100', '18095550100'],
    'extranjero se respeta' => ['34612345678', '34612345678'],
    'vacio' => ['', null],
    'nulo' => [null, null],
    'sin digitos' => ['no-es-un-numero', null],
]);

it('formatea el numero para mostrarlo en pantalla', function () {
    expect($this->wa->formatForDisplay('8095550100'))->toBe('+1 (809) 555-0100');
});

/*
|--------------------------------------------------------------------------
| Generacion del enlace
|--------------------------------------------------------------------------
*/

it('genera el enlace wa.me con el mensaje codificado', function () {
    $enlace = $this->wa->link('809-555-0100', 'Hola, quiero información sobre una propiedad.');

    expect($enlace)->toStartWith('https://wa.me/18095550100?text=')
        ->and($enlace)->toContain('Hola%2C%20quiero%20informaci%C3%B3n');
});

it('genera el enlace sin mensaje si no hay texto', function () {
    expect($this->wa->link('8095550100'))->toBe('https://wa.me/18095550100');
});

it('devuelve null si no hay numero configurado', function () {
    $this->settings->set('contact_whatsapp_number', null);

    expect($this->wa->link())->toBeNull()
        ->and($this->wa->generalLink())->toBeNull();
});

it('usa el numero de la configuracion cuando no se pasa uno', function () {
    $this->settings->set('contact_whatsapp_number', '(829) 111-2222');

    expect($this->wa->generalLink())->toStartWith('https://wa.me/18291112222');
});

/*
|--------------------------------------------------------------------------
| Mensajes
|--------------------------------------------------------------------------
*/

it('sustituye las variables del mensaje de propiedad', function () {
    app()->setLocale('es');

    $mensaje = $this->wa->propertyMessage([
        'reference_code' => 'ERA-1045',
        'title' => 'Apartamento de Lujo Piantini',
    ]);

    expect($mensaje)->toContain('ERA-1045')
        ->and($mensaje)->toContain('Apartamento de Lujo Piantini')
        ->and($mensaje)->not->toContain('{reference_code}')
        ->and($mensaje)->not->toContain('{title}');
});

it('traduce los mensajes segun el idioma', function () {
    app()->setLocale('es');
    expect($this->wa->generalMessage())->toContain('asesoría inmobiliaria');

    app()->setLocale('en');
    expect($this->wa->generalMessage())->toContain('real estate advice');
});

it('deja vacias las variables que no se pasan', function () {
    $mensaje = $this->wa->interpolate('Ref {reference_code} - {title}', [
        'reference_code' => 'ERA-1',
        'title' => null,
    ]);

    expect($mensaje)->toBe('Ref ERA-1 - ');
});

/*
|--------------------------------------------------------------------------
| Boton flotante
|--------------------------------------------------------------------------
*/

it('respeta el interruptor del boton flotante', function () {
    $this->settings->set('whatsapp_float_enabled', true);
    expect($this->wa->isFloatEnabled())->toBeTrue();

    $this->settings->set('whatsapp_float_enabled', false);
    expect($this->wa->isFloatEnabled())->toBeFalse();
});

it('no muestra el boton flotante si no hay numero, aunque este activado', function () {
    $this->settings->set('whatsapp_float_enabled', true);
    $this->settings->set('contact_whatsapp_number', null);

    expect($this->wa->isFloatEnabled())->toBeFalse();
});

it('solo acepta posiciones validas para el boton', function () {
    $this->settings->set('whatsapp_float_position', 'en-el-techo');

    expect($this->wa->floatPosition())->toBe('bottom-right');
});

/*
|--------------------------------------------------------------------------
| Integracion con el sitio publico
|--------------------------------------------------------------------------
*/

it('pinta el boton flotante en el sitio publico', function () {
    $this->settings->set('whatsapp_float_enabled', true);

    $this->get('/')->assertSee('https://wa.me/18095550100', escape: false);
});

it('no pinta el boton flotante cuando esta desactivado', function () {
    $this->settings->set('whatsapp_float_enabled', false);

    $this->get('/')->assertDontSee('wa.me', escape: false);
});

it('el pie usa los datos de configuracion, no texto quemado', function () {
    $this->settings->setMany([
        'site_name' => 'Inmobiliaria Prueba',
        'contact_email' => 'hola@prueba.do',
        'contact_phone' => '+1 (809) 000-1111',
    ]);

    $this->get('/')
        ->assertSee('Inmobiliaria Prueba')
        ->assertSee('hola@prueba.do')
        ->assertSee('+1 (809) 000-1111');
});
