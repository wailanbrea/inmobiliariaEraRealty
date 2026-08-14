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

it('registra las rutas con parametro en ambos idiomas', function () {
    // Ya no se comprueba con un 200: desde la Fase 4 el detalle es real y
    // devuelve 404 si el slug no existe. Lo que importa aqui es que la RUTA
    // exista y resuelva en los dos idiomas.
    expect(Route::has('es.properties.show'))->toBeTrue()
        ->and(Route::has('en.properties.show'))->toBeTrue()
        ->and(lroute('properties.show', ['slug' => 'villa-cap-cana'], 'es'))
        ->toEndWith('/propiedades/villa-cap-cana')
        ->and(lroute('properties.show', ['slug' => 'cap-cana-villa'], 'en'))
        ->toEndWith('/en/properties/cap-cana-villa');
});

it('devuelve 404 en un slug de propiedad inexistente', function () {
    $this->get('/propiedades/no-existe')->assertNotFound();
    $this->get('/en/properties/does-not-exist')->assertNotFound();
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

it('solo la portada atiende al idioma del navegador; las demas paginas no', function () {
    // DECISION REVISADA (petición del cliente, 14/08/2026).
    //
    // Esta prueba afirmaba que el sitio NUNCA redirige por Accept-Language,
    // porque Google desaconseja hacerlo. El cliente pidió lo contrario: que
    // la página cargue de entrada en el idioma del navegador.
    //
    // Se resolvió acotándolo en vez de descartarlo: la redirección ocurre
    // solo en la raíz, solo en la primera visita, nunca para un rastreador y
    // siempre con un 302. Así se cumple lo pedido sin que la portada española
    // le devuelva un redirect eterno a Googlebot.
    //
    // El comportamiento completo vive en BrowserLocaleTest; aquí se fija lo
    // que esta prueba defendía y sigue siendo cierto: una página interior
    // jamás cambia de idioma sola, para que un enlace compartido se vea en el
    // idioma en que se compartió.
    $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get('/propiedades')
        ->assertOk()
        ->assertSee('Propiedades');
});
