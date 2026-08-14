<?php

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use App\Support\Seo;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();
});

/*
|--------------------------------------------------------------------------
| Open Graph
|--------------------------------------------------------------------------
| En este mercado los enlaces circulan por WhatsApp. Una ficha sin tarjeta
| se ve como un enlace sospechoso, asi que ninguna pagina puede quedarse sin.
*/

it('toda pagina publica emite titulo, descripcion y url de open graph', function (string $ruta) {
    $this->get($ruta)
        ->assertOk()
        ->assertSee('property="og:title"', escape: false)
        ->assertSee('property="og:description"', escape: false)
        ->assertSee('property="og:url"', escape: false)
        ->assertSee('name="twitter:card"', escape: false);
})->with(['/', '/propiedades', '/invierte', '/sobre-nosotros', '/contactanos', '/en', '/en/properties']);

it('el og:url es la url canonica de la pagina', function () {
    $this->get('/invierte')->assertSee('property="og:url" content="'.url('/invierte').'"', escape: false);
});

it('usa el logo como imagen por defecto cuando no hay og propia', function () {
    app(SettingsService::class)->set('site_logo', 'settings/logo.webp');
    app(SettingsService::class)->flush();

    $this->get('/')->assertSee('property="og:image" content="'.url('/storage/settings/logo.webp').'"', escape: false);
});

it('la og:image de la propiedad gana sobre la del sitio', function () {
    // Las etiquetas repetidas las resuelve el ultimo valor leido, y la de la
    // vista se empuja despues que la del layout.
    app(SettingsService::class)->set('site_logo', 'settings/logo.webp');
    app(SettingsService::class)->flush();

    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    app(PropertyService::class)->syncTranslations($property, ['es' => ['title' => 'Con portada']]);

    $html = $this->get('/propiedades/con-portada')->assertOk()->getContent();

    $posicionSitio = strpos($html, '/storage/settings/logo.webp');

    // Si la propiedad tiene portada, la suya debe ir despues.
    if ($property->mainImage) {
        expect(strrpos($html, 'og:image'))->toBeGreaterThan($posicionSitio);
    }

    expect($html)->toContain('og:image');
});

/*
|--------------------------------------------------------------------------
| Datos estructurados de la organizacion
|--------------------------------------------------------------------------
*/

it('publica la organizacion como RealEstateAgent', function () {
    $schema = Seo::organization();

    expect($schema['@type'])->toBe('RealEstateAgent')
        ->and($schema['@context'])->toBe('https://schema.org')
        ->and($schema['url'])->toBe(url('/'));
});

it('declara los dos idiomas disponibles', function () {
    expect(Seo::organization()['availableLanguage'])->toBe(['es', 'en']);
});

it('enlaza las redes sociales configuradas con sameAs', function () {
    $ajustes = app(SettingsService::class);
    $ajustes->set('social_facebook', 'https://facebook.com/erarealtyrd');
    $ajustes->set('social_instagram', 'https://instagram.com/erarealtyrd');
    $ajustes->flush();

    expect(Seo::organization()['sameAs'])
        ->toBe(['https://facebook.com/erarealtyrd', 'https://instagram.com/erarealtyrd']);
});

it('omite sameAs cuando no hay ninguna red configurada', function () {
    // Un sameAs vacio es peor que ausente: Google lo lee como dato incompleto.
    expect(Seo::organization())->not->toHaveKey('sameAs');
});

it('inserta la organizacion en el layout publico', function () {
    $this->get('/')
        ->assertSee('application/ld+json', escape: false)
        ->assertSee('RealEstateAgent', escape: false);
});

it('el json-ld de la organizacion es json valido', function () {
    $html = $this->get('/')->getContent();

    preg_match('/<script type="application\/ld\+json">\s*(\{.*?\})\s*<\/script>/s', $html, $coincidencias);

    expect($coincidencias)->not->toBeEmpty()
        ->and(json_decode($coincidencias[1], true))->toBeArray()
        ->and(json_last_error())->toBe(JSON_ERROR_NONE);
});
