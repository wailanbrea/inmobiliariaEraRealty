<?php

use App\Http\Middleware\DetectBrowserLocale;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();
});

/** Petición como la de un navegador real: con idioma y sin parecer un bot. */
function comoNavegador(string $idiomas, string $agente = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140'): array
{
    return ['Accept-Language' => $idiomas, 'User-Agent' => $agente];
}

/*
|--------------------------------------------------------------------------
| Primera visita
|--------------------------------------------------------------------------
*/

it('lleva a ingles si el navegador lo prefiere', function () {
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))
        ->get('/')
        ->assertRedirect(route('en.home'));
});

it('se queda en espanol si el navegador lo prefiere', function () {
    $this->withHeaders(comoNavegador('es-DO,es;q=0.9'))->get('/')->assertOk();
});

it('respeta los pesos de la cabecera', function () {
    // 'fr' no está soportado; entre los que sí, inglés pesa más que español.
    $this->withHeaders(comoNavegador('fr-FR,fr;q=1.0,en;q=0.8,es;q=0.5'))
        ->get('/')
        ->assertRedirect(route('en.home'));
});

it('se queda en espanol si el idioma del navegador no esta soportado', function () {
    $this->withHeaders(comoNavegador('de-DE,de;q=0.9'))->get('/')->assertOk();
});

it('se queda en espanol si el navegador no declara idioma', function () {
    // Symfony inyecta una cabecera por defecto en toda petición de prueba, así
    // que la única forma de simular su ausencia es enviarla vacía.
    $this->withHeaders(comoNavegador(''))->get('/')->assertOk();
});

/*
|--------------------------------------------------------------------------
| La elección del visitante manda sobre la del navegador
|--------------------------------------------------------------------------
*/

it('deja de redirigir cuando ya hay una eleccion guardada', function () {
    // Sin esto, pulsar «ES» desde /en y volver a la portada te devolvería a
    // inglés: el sitio ignorando lo que acabas de pedirle.
    $this->withCookie(DetectBrowserLocale::COOKIE, 'es')
        ->withHeaders(comoNavegador('en-US,en;q=0.9'))
        ->get('/')
        ->assertOk();
});

it('guarda el idioma de la pagina que se esta viendo', function () {
    $this->get('/')->assertCookie(DetectBrowserLocale::COOKIE, 'es');
    $this->get('/en')->assertCookie(DetectBrowserLocale::COOKIE, 'en');
});

it('el ciclo completo: llega en ingles, cambia a espanol y ahi se queda', function () {
    // 1. Primera visita con navegador en inglés
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))->get('/')->assertRedirect(route('en.home'));

    // 2. Ve la portada inglesa; se guarda su idioma
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))->get('/en')
        ->assertOk()->assertCookie(DetectBrowserLocale::COOKIE, 'en');

    // 3. Pulsa «ES»: navega a la raíz y la cookie pasa a español
    $this->withCookie(DetectBrowserLocale::COOKIE, 'en')
        ->withHeaders(comoNavegador('en-US,en;q=0.9'))
        ->get('/')
        ->assertOk()
        ->assertCookie(DetectBrowserLocale::COOKIE, 'es');

    // 4. Vuelve a la portada y NO se le devuelve a inglés
    $this->withCookie(DetectBrowserLocale::COOKIE, 'es')
        ->withHeaders(comoNavegador('en-US,en;q=0.9'))
        ->get('/')
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
| Google desaconseja redirigir por Accept-Language: su rastreador entra desde
| EE. UU. anunciando inglés, y si la portada española siempre le devuelve un
| 302 puede terminar sin indexarse.
*/

it('nunca redirige a un rastreador', function (string $agente) {
    $this->withHeaders(comoNavegador('en-US,en;q=0.9', $agente))->get('/')->assertOk();
})->with([
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'facebookexternalhit/1.1',
    'WhatsApp/2.23',
    'Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120 Lighthouse',
]);

it('solo actua en la raiz, nunca en una pagina interior', function (string $ruta) {
    // Un enlace compartido debe verse en el idioma en que se compartió.
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))->get($ruta)->assertOk();
})->with(['/propiedades', '/invierte', '/sobre-nosotros', '/contactanos', '/informate']);

it('la redireccion es temporal, no permanente', function () {
    // Un 301 se queda cacheado en el navegador y deja al visitante sin forma
    // de volver a la raíz española.
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))->get('/')->assertStatus(302);
});

it('no toca el panel', function () {
    $this->withHeaders(comoNavegador('en-US,en;q=0.9'))
        ->get('/admin/login')
        ->assertOk();
});

it('la portada espanola sigue declarando su hreflang', function () {
    // Es lo que le dice a Google que existen las dos versiones, y sustituye
    // con creces a redirigir a su rastreador.
    $this->withHeaders(comoNavegador('es-DO'))
        ->get('/')
        ->assertSee('hreflang="en"', escape: false)
        ->assertSee('hreflang="x-default"', escape: false);
});
