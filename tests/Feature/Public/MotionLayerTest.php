<?php

use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
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

/*
|--------------------------------------------------------------------------
| La garantia que sostiene toda la capa de efectos
|--------------------------------------------------------------------------
| Los elementos animados se renderizan VISIBLES. Es motion.js quien les pone
| .is-primed para ocultarlos justo antes de animarlos.
|
| Al reves —ocultar en CSS y confiar en que el JS revele— basta un bloqueador
| de scripts o un error de red para servir una pagina en blanco. Estas pruebas
| son las que impiden que alguien invierta ese orden sin darse cuenta.
*/

it('ningun elemento revelable se sirve ya oculto', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('data-reveal');

    // Se inspecciona cada etiqueta con data-reveal, no el documento entero:
    // un opacity-0 suelto en el tooltip del boton de WhatsApp es decoracion
    // de hover y no tiene nada que ver con esta garantia.
    preg_match_all('/<[a-z]+[^>]*\bdata-reveal\b[^>]*>/i', $html, $etiquetas);

    expect($etiquetas[0])->not->toBeEmpty();

    foreach ($etiquetas[0] as $etiqueta) {
        expect($etiqueta)
            ->not->toContain('is-primed')
            ->not->toContain('opacity-0')
            ->not->toContain('invisible')
            ->not->toContain('x-cloak');
    }
});

it('el titulo del hero se sirve como texto legible dentro de la mascara', function () {
    // Aunque el JS no llegue, el <span> esta en su sitio y sin transformar.
    $hero = ContentSection::where('page_key', 'home')->where('section_key', 'hero')->first();

    $html = $this->get('/')->getContent();

    expect($html)->toContain('line-mask')
        ->and($html)->toContain($hero?->title ?: __('home.hero.title'));
});

it('el contador muestra ya la cifra final en el html', function () {
    // Si el JS falla, el visitante lee el numero correcto, no un cero.
    ContentSection::updateOrCreate(
        ['page_key' => 'home', 'section_key' => 'stats'],
        ['is_active' => true, 'sort_order' => 40, 'extra_json' => [
            ['value' => '450', 'suffix' => '+', 'label_es' => 'Propiedades', 'label_en' => 'Properties'],
        ]]
    );

    ContentSection::flushCache();

    $this->get('/')
        ->assertSee('data-counter="450"', escape: false)
        ->assertSee('450+', escape: false);
});

/*
|--------------------------------------------------------------------------
| Enganches que el JS necesita encontrar
|--------------------------------------------------------------------------
*/

it('el hero declara sus capas de parallax y el Ken Burns', function () {
    $hero = ContentSection::where('page_key', 'home')->where('section_key', 'hero')->firstOrFail();
    $hero->update(['image' => 'content/hero.webp']);

    $html = $this->get('/')->getContent();

    // El escenario, mas las capas que se mueven a distinta velocidad: fondo
    // (+30), titulo y subtitulo (-15) y buscador (-5). El limite de docs/13
    // son tres profundidades distintas; el subtitulo comparte plano con el
    // titulo, asi que siguen siendo tres.
    $profundidades = [];
    preg_match_all('/data-parallax="(-?\d+)"/', $html, $coincidencias);
    $profundidades = array_unique($coincidencias[1]);

    expect($html)->toContain('data-parallax-scene')
        ->and($html)->toContain('data-ken-burns')
        ->and(count($profundidades))->toBe(3)
        ->and($profundidades)->toContain('30');
});

it('sin foto de portada no se pinta la capa de fondo ni el Ken Burns', function () {
    ContentSection::where('page_key', 'home')->where('section_key', 'hero')
        ->update(['image' => null]);
    ContentSection::flushCache();

    $html = $this->get('/')->getContent();

    expect($html)->not->toContain('data-ken-burns');
});

it('la cabecera lleva el enganche de condensado', function () {
    $this->get('/')->assertSee('data-header', escape: false);
});

it('la tarjeta de propiedad marca desde donde despega el vuelo al comparador', function () {
    destacadaConTitulo('Con enganche');

    $this->get('/')->assertOk()->assertSee('data-compare-card', escape: false);
});

it('el boton de comparar declara si la propiedad ya esta marcada', function () {
    $property = destacadaConTitulo('Para comparar');

    $this->get('/')->assertOk()->assertSee('aria-pressed="false"', escape: false);

    $this->post(route('es.compare.toggle', ['property' => $property->id]));

    $this->get('/')->assertOk()->assertSee('aria-pressed="true"', escape: false);
});

it('una propiedad sin traduccion no tumba la portada', function () {
    // Sin slug no se puede construir su enlace, y eso devolvia un 500 en la
    // portada entera. Se omite la ficha y el resto del listado sobrevive.
    Property::factory()->published()->featured()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    destacadaConTitulo('Esta si tiene titulo');

    $this->get('/')
        ->assertOk()
        ->assertSee('Esta si tiene titulo', escape: false);
});

function destacadaConTitulo(string $titulo): Property
{
    $property = Property::factory()->published()->featured()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    app(PropertyService::class)
        ->syncTranslations($property, ['es' => ['title' => $titulo]]);

    return $property->fresh();
}

/*
|--------------------------------------------------------------------------
| Accesibilidad del lightbox
|--------------------------------------------------------------------------
*/

it('el lightbox se anuncia como dialogo modal', function () {
    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    app(PropertyService::class)
        ->syncTranslations($property, ['es' => ['title' => 'Con galeria']]);

    PropertyImage::factory()->create([
        'property_id' => $property->id,
        'is_main' => true,
        'sort_order' => 0,
    ]);

    $html = $this->get('/propiedades/con-galeria')->assertOk()->getContent();

    expect($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('x-data="gallery(');
});
