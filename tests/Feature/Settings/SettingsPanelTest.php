<?php

use App\Modules\Settings\Mail\TestMail;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->settings = app(SettingsService::class);
    $this->settings->flush();
    $this->admin = userWithRole('super_admin');
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para entrar en configuracion', function (string $ruta) {
    $this->get($ruta)->assertRedirect(route('admin.login'));
})->with([
    '/admin/configuracion/general',
    '/admin/configuracion/whatsapp',
    '/admin/configuracion/correo',
    '/admin/configuracion/seo',
]);

it('muestra las cuatro pestanas al administrador', function (string $ruta) {
    $this->actingAs($this->admin)->get($ruta)->assertOk();
})->with([
    '/admin/configuracion/general',
    '/admin/configuracion/whatsapp',
    '/admin/configuracion/correo',
    '/admin/configuracion/seo',
]);

it('niega el acceso a un rol sin permiso de configuracion', function () {
    $editor = userWithRole('editor');

    $this->actingAs($editor)
        ->put('/admin/configuracion/general', ['site_name' => 'Intento'])
        ->assertForbidden();

    expect($this->settings->get('site_name'))->not->toBe('Intento');
});

/*
|--------------------------------------------------------------------------
| General
|--------------------------------------------------------------------------
*/

it('guarda los datos generales', function () {
    $this->actingAs($this->admin)
        ->put('/admin/configuracion/general', [
            'site_name' => 'Inmobiliaria del Este',
            'contact_email' => 'hola@este.do',
            'contact_phone' => '(809) 222-3333',
            'currency_default' => 'USD',
            'currency_usd_to_dop' => '61.75',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->settings->flush();

    expect($this->settings->get('site_name'))->toBe('Inmobiliaria del Este')
        ->and($this->settings->get('contact_email'))->toBe('hola@este.do')
        ->and($this->settings->get('currency_usd_to_dop'))->toBe(61.75);
});

it('guarda los campos traducibles en los dos idiomas', function () {
    $this->actingAs($this->admin)
        ->put('/admin/configuracion/general', [
            'site_name' => 'ERA Realty RD',
            'currency_default' => 'USD',
            'currency_usd_to_dop' => '60',
            'footer_text' => ['es' => 'Texto en español', 'en' => 'Text in English'],
        ])
        ->assertSessionHasNoErrors();

    $this->settings->flush();

    app()->setLocale('es');
    expect($this->settings->get('footer_text'))->toBe('Texto en español');

    app()->setLocale('en');
    expect($this->settings->get('footer_text'))->toBe('Text in English');
});

it('rechaza guardar sin nombre de inmobiliaria', function () {
    $this->actingAs($this->admin)
        ->put('/admin/configuracion/general', [
            'site_name' => '',
            'currency_default' => 'USD',
            'currency_usd_to_dop' => '60',
        ])
        ->assertSessionHasErrors('site_name');
});

it('rechaza una tasa de cambio no numerica', function () {
    $this->actingAs($this->admin)
        ->put('/admin/configuracion/general', [
            'site_name' => 'ERA',
            'currency_default' => 'USD',
            'currency_usd_to_dop' => 'sesenta',
        ])
        ->assertSessionHasErrors('currency_usd_to_dop');
});

it('anota cuando se actualizo la tasa de cambio', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '62.10',
    ]);

    $this->settings->flush();

    expect($this->settings->get('currency_rate_updated_at'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Logo y favicon
|--------------------------------------------------------------------------
*/

it('sube el logo y lo guarda como ruta relativa', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => UploadedFile::fake()->image('logo.png', 800, 300),
    ])->assertSessionHasNoErrors();

    $this->settings->flush();
    $ruta = $this->settings->get('site_logo');

    expect($ruta)->toStartWith('settings/')
        ->and($ruta)->not->toContain('http')      // nunca ruta absoluta
        ->and($ruta)->not->toContain('logo.png')  // nunca el nombre original
        ->and(Storage::disk('public')->exists($ruta))->toBeTrue();
});

it('rechaza un php disfrazado de imagen', function () {
    Storage::fake('public');

    $malicioso = UploadedFile::fake()->createWithContent(
        'shell.php.png',
        '<?php system($_GET["cmd"]); ?>'
    );

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => $malicioso,
    ])->assertSessionHasErrors('site_logo');

    // Lo que importa no es que el logo quede vacio —el seeder ya trae el de
    // la marca— sino que el fichero malicioso NO llegue a sustituirlo.
    $this->settings->flush();
    expect($this->settings->get('site_logo'))->not->toContain('shell')
        ->and($this->settings->get('site_logo'))->not->toEndWith('.php');
});

it('acepta un SVG limpio como logo', function () {
    Storage::fake('public');

    $svg = UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 40"><rect width="100" height="40"/></svg>'
    );

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => $svg,
    ])->assertSessionHasNoErrors();

    $this->settings->flush();
    expect($this->settings->get('site_logo'))->toEndWith('.svg');
});

it('rechaza un SVG con script incrustado', function () {
    Storage::fake('public');

    $svg = UploadedFile::fake()->createWithContent(
        'malicioso.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>fetch("/admin")</script></svg>'
    );

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => $svg,
    ])->assertSessionHasErrors('site_logo');

    // Igual que arriba: el SVG con script no puede sustituir al logo actual.
    $this->settings->flush();
    expect($this->settings->get('site_logo'))->not->toEndWith('.svg');
});

it('rechaza un SVG con manejador onload', function () {
    Storage::fake('public');

    $svg = UploadedFile::fake()->createWithContent(
        'onload.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect/></svg>'
    );

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => $svg,
    ])->assertSessionHasErrors('site_logo');
});

it('no admite SVG en la imagen de redes sociales', function () {
    Storage::fake('public');

    $svg = UploadedFile::fake()->createWithContent(
        'og.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>'
    );

    // Facebook y X no renderizan SVG en las tarjetas: se rechaza de entrada.
    $this->actingAs($this->admin)->put('/admin/configuracion/seo', [
        'seo_default_og_image' => $svg,
    ])->assertSessionHasErrors('seo_default_og_image');
});

it('rechaza una imagen demasiado pesada', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => UploadedFile::fake()->image('grande.png')->size(3000),
    ])->assertSessionHasErrors('site_logo');
});

it('rechaza un favicon mas pequeno de 96px', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_favicon' => UploadedFile::fake()->image('fav.png', 32, 32),
    ])->assertSessionHasErrors('site_favicon');
});

it('permite quitar una imagen y borra el fichero', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->put('/admin/configuracion/general', [
        'site_name' => 'ERA',
        'currency_default' => 'USD',
        'currency_usd_to_dop' => '60',
        'site_logo' => UploadedFile::fake()->image('logo.png', 400, 200),
    ]);

    $this->settings->flush();
    $ruta = $this->settings->get('site_logo');

    $this->actingAs($this->admin)
        ->delete('/admin/configuracion/imagen/site_logo')
        ->assertRedirect();

    $this->settings->flush();

    expect($this->settings->get('site_logo'))->toBeNull()
        ->and(Storage::disk('public')->exists($ruta))->toBeFalse();
});

it('no acepta borrar una clave que no es de imagen', function () {
    $this->actingAs($this->admin)
        ->delete('/admin/configuracion/imagen/mail_password')
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| WhatsApp
|--------------------------------------------------------------------------
*/

it('guarda el numero de whatsapp y genera el enlace', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/whatsapp', [
        'contact_whatsapp_number' => '(829) 444-5555',
        'whatsapp_float_position' => 'bottom-left',
        'whatsapp_float_enabled' => '1',
    ])->assertSessionHasNoErrors();

    $this->settings->flush();

    expect(whatsapp()->generalLink())->toStartWith('https://wa.me/18294445555')
        ->and(whatsapp()->floatPosition())->toBe('bottom-left');
});

it('rechaza un numero de whatsapp invalido', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/whatsapp', [
        'contact_whatsapp_number' => '123',
        'whatsapp_float_position' => 'bottom-right',
    ])->assertSessionHasErrors('contact_whatsapp_number');
});

it('rechaza una posicion de boton inventada', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/whatsapp', [
        'contact_whatsapp_number' => '8095550100',
        'whatsapp_float_position' => 'en-el-techo',
    ])->assertSessionHasErrors('whatsapp_float_position');
});

it('muestra el enlace generado en la pantalla', function () {
    $this->actingAs($this->admin)
        ->get('/admin/configuracion/whatsapp')
        ->assertSee('https://wa.me/18095550100', escape: false);
});

/*
|--------------------------------------------------------------------------
| Correo
|--------------------------------------------------------------------------
*/

it('guarda la configuracion de correo con la contrasena cifrada', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/correo', [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.prueba.do',
        'mail_port' => '587',
        'mail_username' => 'usuario',
        'mail_password' => 'clave-secreta',
        'mail_encryption' => 'tls',
        'mail_from_address' => 'no-reply@prueba.do',
        'mail_from_name' => 'Prueba',
    ])->assertSessionHasNoErrors();

    $this->settings->flush();

    expect($this->settings->get('mail_host'))->toBe('smtp.prueba.do')
        ->and($this->settings->get('mail_password'))->toBe('clave-secreta');

    $enBruto = Setting::where('key', 'mail_password')->value('value');
    expect($enBruto)->not->toContain('clave-secreta');
});

it('conserva la contrasena si el campo se deja vacio', function () {
    $this->settings->set('mail_password', 'clave-original');

    $this->actingAs($this->admin)->put('/admin/configuracion/correo', [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.prueba.do',
        'mail_port' => '587',
        'mail_password' => '',
        'mail_from_address' => 'no-reply@prueba.do',
        'mail_from_name' => 'Prueba',
    ]);

    $this->settings->flush();

    expect($this->settings->get('mail_password'))->toBe('clave-original');
});

it('nunca devuelve la contrasena al formulario', function () {
    $this->settings->set('mail_password', 'clave-super-secreta');

    $this->actingAs($this->admin)
        ->get('/admin/configuracion/correo')
        ->assertDontSee('clave-super-secreta');
});

it('envia el correo de prueba y guarda si funciona', function () {
    Mail::fake();

    $this->actingAs($this->admin)->put('/admin/configuracion/correo', [
        'mail_mailer' => 'log',
        'mail_host' => 'smtp.prueba.do',
        'mail_port' => '587',
        'mail_from_address' => 'no-reply@prueba.do',
        'mail_from_name' => 'Prueba',
        'send_test' => '1',
        'test_recipient' => 'admin@prueba.do',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(TestMail::class);

    $this->settings->flush();
    expect($this->settings->get('mail_host'))->toBe('smtp.prueba.do');
});

it('NO guarda la configuracion si el correo de prueba falla', function () {
    // Es la garantia central del prompt maestro (§7): no dejar activas unas
    // credenciales que no funcionan.
    $this->settings->set('mail_host', 'smtp.que-si-funciona.do');

    Mail::shouldReceive('purge')->andReturnNull();
    Mail::shouldReceive('to->send')->andThrow(new Exception('535 Auth failed'));

    $this->actingAs($this->admin)->put('/admin/configuracion/correo', [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.roto.do',
        'mail_port' => '587',
        'mail_from_address' => 'no-reply@prueba.do',
        'mail_from_name' => 'Prueba',
        'send_test' => '1',
        'test_recipient' => 'admin@prueba.do',
    ])->assertSessionHasErrors('mail_test');

    $this->settings->flush();

    expect($this->settings->get('mail_host'))->toBe('smtp.que-si-funciona.do');
});

it('exige un destinatario para la prueba', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/correo', [
        'mail_mailer' => 'log',
        'mail_from_address' => 'no-reply@prueba.do',
        'mail_from_name' => 'Prueba',
        'send_test' => '1',
        'test_recipient' => '',
    ])->assertSessionHasErrors('test_recipient');
});

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/

it('guarda el SEO global en ambos idiomas', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/seo', [
        'seo_default_title' => ['es' => 'Título ES', 'en' => 'Title EN'],
        'seo_default_description' => ['es' => 'Descripción ES', 'en' => 'Description EN'],
    ])->assertSessionHasNoErrors();

    $this->settings->flush();

    app()->setLocale('en');
    expect($this->settings->get('seo_default_title'))->toBe('Title EN');
});

it('rechaza un ID de analytics con formato invalido', function () {
    $this->actingAs($this->admin)->put('/admin/configuracion/seo', [
        'seo_google_analytics_id' => 'no-es-un-id',
    ])->assertSessionHasErrors('seo_google_analytics_id');
});

it('acepta los formatos validos de analytics', function (string $id) {
    $this->actingAs($this->admin)->put('/admin/configuracion/seo', [
        'seo_google_analytics_id' => $id,
    ])->assertSessionHasNoErrors();
})->with(['G-ABC1234567', 'UA-12345678-1', 'GTM-ABC1234']);
