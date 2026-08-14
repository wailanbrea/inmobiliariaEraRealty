<?php

use App\Enums\PropertyStatus;
use App\Modules\News\Models\NewsPost;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();
});

/*
|--------------------------------------------------------------------------
| Sitemap
|--------------------------------------------------------------------------
*/

it('sirve el sitemap como xml valido', function () {
    $respuesta = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    // Si el Blade colara un solo byte antes del prologo, esto reventaria.
    $xml = simplexml_load_string($respuesta->getContent());

    expect($xml)->not->toBeFalse()
        ->and($xml->getName())->toBe('urlset');
});

it('el prologo xml es el primer byte del documento', function () {
    $contenido = $this->get('/sitemap.xml')->getContent();

    expect($contenido)->toStartWith('<?xml version="1.0"');
});

it('lista la portada en los dos idiomas', function () {
    $contenido = $this->get('/sitemap.xml')->getContent();

    expect($contenido)->toContain('<loc>'.route('es.home').'</loc>')
        ->and($contenido)->toContain('<loc>'.route('en.home').'</loc>');
});

it('cada url declara sus alternativas hreflang y el x-default', function () {
    $contenido = $this->get('/sitemap.xml')->getContent();

    expect($contenido)->toContain('hreflang="es"')
        ->and($contenido)->toContain('hreflang="en"')
        ->and($contenido)->toContain('hreflang="x-default"');
});

it('incluye una propiedad publicada en ambos idiomas', function () {
    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Available,
    ]);

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => 'Villa en Cap Cana'],
        'en' => ['title' => 'Villa in Cap Cana'],
    ]);

    $contenido = $this->get('/sitemap.xml')->getContent();

    expect($contenido)->toContain('villa-en-cap-cana')
        ->and($contenido)->toContain('villa-in-cap-cana');
});

it('una propiedad sin traducir solo aparece en su idioma', function () {
    // Declarar un alternate que devuelve 404 es peor que no declarar ninguno.
    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Available,
    ]);

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => 'Solo en espanol'],
    ]);

    $contenido = $this->get('/sitemap.xml')->getContent();
    $xml = simplexml_load_string($contenido);

    $suya = collect($xml->url)->first(fn ($url) => str_contains((string) $url->loc, 'solo-en-espanol'));

    expect($suya)->not->toBeNull();

    // Aparece una sola vez como <loc> y no declara ningun alternate en ingles:
    // apuntar a un 404 desde el sitemap es peor que no apuntar a nada.
    // Con foreach y no con collect(): SimpleXML entrega los hijos homonimos
    // bajo una sola clave, asi que collect() se quedaria solo con el ultimo.
    $idiomas = [];

    foreach ($suya->children('xhtml', true) as $link) {
        $idiomas[] = (string) $link->attributes()->hreflang;
    }

    expect($idiomas)->toBe(['es', 'x-default'])
        ->and(substr_count($contenido, '<loc>'.route('es.properties.show', ['slug' => 'solo-en-espanol']).'</loc>'))->toBe(1);
});

it('excluye las propiedades vendidas y alquiladas', function () {
    foreach ([PropertyStatus::Sold, PropertyStatus::Rented] as $estado) {
        $property = Property::factory()->published()->create([
            'property_type_id' => PropertyType::first()->id,
            'status' => $estado,
        ]);

        app(PropertyService::class)->syncTranslations($property, [
            'es' => ['title' => 'Fuera del sitemap '.$estado->value],
        ]);
    }

    expect($this->get('/sitemap.xml')->getContent())
        ->not->toContain('fuera-del-sitemap');
});

it('excluye los borradores', function () {
    $property = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'published_at' => null,
    ]);

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => 'Borrador oculto'],
    ]);

    expect($this->get('/sitemap.xml')->getContent())->not->toContain('borrador-oculto');
});

it('excluye una noticia sin publicar', function () {
    NewsPost::factory()->create(['published_at' => null]);

    $contenido = $this->get('/sitemap.xml')->getContent();
    $xml = simplexml_load_string($contenido);

    // Solo deben quedar las paginas fijas: 7 rutas x 2 idiomas.
    expect(count($xml->url))->toBe(14);
});

it('no lista privacidad ni terminos', function () {
    $contenido = $this->get('/sitemap.xml')->getContent();

    expect($contenido)->not->toContain('<loc>'.route('es.privacy').'</loc>')
        ->and($contenido)->not->toContain('<loc>'.route('es.terms').'</loc>');
});

/*
|--------------------------------------------------------------------------
| robots.txt
|--------------------------------------------------------------------------
*/

it('sirve robots.txt como texto plano', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('robots.txt siempre apunta al sitemap', function () {
    // Es la linea que hace que Google lo encuentre sin registrarlo a mano.
    $this->get('/robots.txt')->assertSee('Sitemap: '.route('sitemap'), escape: false);
});

it('robots.txt respeta lo que escribe el administrador', function () {
    app(SettingsService::class)->set('seo_robots_txt', "User-agent: *\nDisallow: /privado");
    app(SettingsService::class)->flush();

    $this->get('/robots.txt')
        ->assertSee('Disallow: /privado', escape: false)
        ->assertSee('Sitemap:', escape: false);
});

it('no duplica la linea del sitemap si el administrador ya la puso', function () {
    app(SettingsService::class)->set('seo_robots_txt', "User-agent: *\nSitemap: https://ejemplo.test/otro.xml");
    app(SettingsService::class)->flush();

    $cuerpo = $this->get('/robots.txt')->getContent();

    expect(substr_count(strtolower($cuerpo), 'sitemap:'))->toBe(1);
});

it('por defecto bloquea el panel', function () {
    $this->get('/robots.txt')->assertSee('Disallow: /admin', escape: false);
});
