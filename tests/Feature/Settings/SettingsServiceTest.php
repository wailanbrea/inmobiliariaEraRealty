<?php

use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->settings = app(SettingsService::class);
    $this->settings->flush();
});

it('crea las 42 claves del catalogo', function () {
    expect(Setting::count())->toBe(count(SettingsSeeder::definitions()));
});

it('devuelve el valor por defecto si la clave no existe', function () {
    expect($this->settings->get('clave_inventada', 'respaldo'))->toBe('respaldo');
});

it('aplica el tipo declarado', function () {
    $this->settings->set('whatsapp_float_enabled', true);
    $this->settings->set('currency_usd_to_dop', '61.25');
    $this->settings->set('mail_port', '465');

    expect($this->settings->get('whatsapp_float_enabled'))->toBeTrue()
        ->and($this->settings->get('currency_usd_to_dop'))->toBe(61.25)
        ->and($this->settings->get('mail_port'))->toBe(465);
});

it('guarda booleanos falsos sin confundirlos con vacio', function () {
    $this->settings->set('whatsapp_float_enabled', false);

    expect($this->settings->get('whatsapp_float_enabled'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Traduccion
|--------------------------------------------------------------------------
*/

it('resuelve las claves traducibles segun el idioma', function () {
    app()->setLocale('es');
    expect($this->settings->get('site_tagline'))->toContain('Bienes raíces');

    app()->setLocale('en');
    expect($this->settings->get('site_tagline'))->toContain('Real estate');
});

it('cae al espanol cuando falta la traduccion inglesa', function () {
    $this->settings->set('site_tagline', ['es' => 'Solo español', 'en' => '']);

    app()->setLocale('en');

    expect($this->settings->get('site_tagline'))->toBe('Solo español');
});

it('expone las traducciones por separado para el panel', function () {
    $traducciones = $this->settings->translations('site_tagline');

    expect($traducciones)->toHaveKeys(['es', 'en'])
        ->and($traducciones['es'])->toContain('Bienes raíces')
        ->and($traducciones['en'])->toContain('Real estate');
});

/*
|--------------------------------------------------------------------------
| Seguridad
|--------------------------------------------------------------------------
*/

it('cifra la contrasena del correo en base de datos', function () {
    $this->settings->set('mail_password', 'secreto-smtp');

    $enBruto = Setting::where('key', 'mail_password')->value('value');

    expect($enBruto)->not->toBe('secreto-smtp')
        ->and(Crypt::decryptString($enBruto))->toBe('secreto-smtp')
        ->and($this->settings->get('mail_password'))->toBe('secreto-smtp');
});

it('no revienta si la clave de cifrado ya no sirve', function () {
    Setting::where('key', 'mail_password')->update(['value' => 'basura-no-descifrable']);
    $this->settings->flush();

    // Devuelve null en lugar de lanzar: el sitio sigue en pie.
    expect($this->settings->get('mail_password'))->toBeNull();
});

it('deja fuera de las vistas publicas las claves privadas', function () {
    $publicas = $this->settings->publicValues();

    expect($publicas)->toHaveKey('site_name')
        ->and($publicas)->toHaveKey('contact_email')
        ->and($publicas)->not->toHaveKey('mail_password')
        ->and($publicas)->not->toHaveKey('mail_host')
        ->and($publicas)->not->toHaveKey('contact_form_recipient_email')
        ->and($publicas)->not->toHaveKey('seo_google_analytics_id');
});

/*
|--------------------------------------------------------------------------
| Cache e integridad
|--------------------------------------------------------------------------
*/

it('cachea el conjunto y lo invalida al guardar', function () {
    $this->settings->all();
    // La clave lleva version: subirla es como se invalida la cache al
    // cambiar la forma de los ajustes. La prueba la lee del servicio en
    // vez de repetirla, o se rompe en cada version nueva.
    $clave = (new ReflectionClass(SettingsService::class))->getConstant('CACHE_KEY');
    expect(Cache::has($clave))->toBeTrue();

    $this->settings->set('site_name', 'Otro nombre');
    expect(Cache::has('settings.all'))->toBeFalse();

    expect($this->settings->get('site_name'))->toBe('Otro nombre');
});

it('ignora claves que no estan en el catalogo', function () {
    // Un typo en un formulario no debe ensuciar la tabla.
    expect($this->settings->set('clave_fantasma', 'x'))->toBeFalse()
        ->and(Setting::where('key', 'clave_fantasma')->exists())->toBeFalse();
});

it('el seeder es idempotente y respeta los valores ya cambiados', function () {
    $this->settings->set('site_name', 'Nombre del cliente');

    $this->seed(SettingsSeeder::class);
    $this->settings->flush();

    expect($this->settings->get('site_name'))->toBe('Nombre del cliente')
        ->and(Setting::count())->toBe(count(SettingsSeeder::definitions()));
});
