<?php

use App\Support\Locale;

/*
|--------------------------------------------------------------------------
| Sitio bilingue espanol / ingles
| Ver docs/15_I18N.md
|--------------------------------------------------------------------------
*/

it('sirve el espanol sin prefijo y el ingles con /en', function () {
    $this->get('/')->assertOk();
    $this->get('/en')->assertOk();
});

it('traduce los segmentos de URL', function (string $es, string $en) {
    $this->get($es)->assertOk();
    $this->get($en)->assertOk();
})->with([
    ['/propiedades', '/en/properties'],
    ['/invierte', '/en/invest'],
    ['/sobre-nosotros', '/en/about-us'],
    ['/informate', '/en/insights'],
    ['/contactanos', '/en/contact'],
    ['/publica-tu-propiedad', '/en/list-your-property'],
    ['/comparar', '/en/compare'],
    ['/privacidad', '/en/privacy'],
    ['/terminos', '/en/terms'],
]);

it('no responde a segmentos cruzados entre idiomas', function () {
    // /en/propiedades no debe existir: el ingles usa properties.
    $this->get('/en/propiedades')->assertNotFound();
    $this->get('/properties')->assertNotFound();
});

it('fija el idioma de la aplicacion segun el prefijo', function () {
    $this->get('/propiedades');
    expect(app()->getLocale())->toBe('es');

    $this->get('/en/properties');
    expect(app()->getLocale())->toBe('en');
});

it('muestra los textos de interfaz en el idioma de la URL', function () {
    $this->get('/')
        ->assertSee('Propiedades')
        ->assertSee('Sobre Nosotros')
        ->assertDontSee('About Us');

    $this->get('/en')
        ->assertSee('Properties')
        ->assertSee('About Us')
        ->assertDontSee('Sobre Nosotros');
});

it('declara el atributo lang correcto', function () {
    $this->get('/')->assertSee('<html lang="es"', escape: false);
    $this->get('/en')->assertSee('<html lang="en"', escape: false);
});

it('publica hreflang de ambos idiomas y x-default al espanol', function () {
    $respuesta = $this->get('/propiedades');

    $respuesta->assertSee('hreflang="es"', escape: false);
    $respuesta->assertSee('hreflang="en"', escape: false);
    $respuesta->assertSee('hreflang="x-default"', escape: false);

    // x-default debe apuntar al espanol, no al ingles.
    $html = $respuesta->getContent();
    preg_match('/hreflang="x-default" href="([^"]+)"/', $html, $m);
    expect($m[1])->toContain('/propiedades')
        ->and($m[1])->not->toContain('/en/');
});

it('declara og:locale y su alternativa', function () {
    $this->get('/')
        ->assertSee('property="og:locale" content="es_DO"', escape: false)
        ->assertSee('property="og:locale:alternate" content="en_US"', escape: false);
});

it('el canonical apunta a la propia pagina, nunca al otro idioma', function () {
    $html = $this->get('/en/properties')->getContent();

    preg_match('/rel="canonical" href="([^"]+)"/', $html, $m);
    expect($m[1])->toContain('/en/properties');
});

it('el selector lleva a la misma pagina en el otro idioma', function () {
    // Desde /invierte, el selector debe apuntar a /en/invest, no a la portada.
    $html = $this->get('/invierte')->getContent();

    expect($html)->toContain('/en/invest');
});

it('lroute genera la URL del idioma pedido', function () {
    expect(lroute('properties.index', [], 'es'))->toEndWith('/propiedades')
        ->and(lroute('properties.index', [], 'en'))->toEndWith('/en/properties');
});

it('Locale::alternateUrl devuelve el equivalente en el otro idioma', function () {
    $this->get('/sobre-nosotros');

    expect(Locale::alternateUrl('en'))->toContain('/en/about-us');
});

it('resuelve rutas con parametro conservando el slug', function () {
    $this->get('/propiedades/villa-cap-cana')->assertOk();
    $this->get('/en/properties/cap-cana-villa')->assertOk();
});

it('no deja ninguna clave de traduccion sin resolver', function (string $url) {
    // Cuando __() no encuentra la clave, devuelve la clave tal cual y acaba
    // impresa en la pagina ("common.nav.invest"). Se escapo una en la Fase 0
    // porque las pruebas solo miraban el layout, no el contenido.
    $html = $this->get($url)->getContent();

    // Se ignora lo que hay dentro de <script> y de los atributos de Alpine.
    $texto = strip_tags($html);

    expect($texto)->not->toMatch('/\b(common|home|property|forms|seo)\.[a-z_]+(\.[a-z_]+)+\b/');
})->with([
    '/', '/en',
    '/propiedades', '/en/properties',
    '/invierte', '/en/invest',
    '/sobre-nosotros', '/en/about-us',
    '/informate', '/en/insights',
    '/contactanos', '/en/contact',
    '/publica-tu-propiedad', '/en/list-your-property',
    '/comparar', '/en/compare',
    '/privacidad', '/en/privacy',
    '/terminos', '/en/terms',
]);

it('traduce tambien el titulo de las paginas internas', function () {
    $this->get('/invierte')->assertSee('<title>Invierte', escape: false);
    $this->get('/en/invest')->assertSee('<title>Invest', escape: false);
});

it('no redirige automaticamente por el idioma del navegador', function () {
    // Google penaliza la redireccion automatica al rastrear.
    $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get('/')
        ->assertOk()
        ->assertSee('Propiedades');
});
