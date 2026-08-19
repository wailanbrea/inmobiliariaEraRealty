<?php

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();
});

function fichaConDatos(array $atributos = [], string $titulo = 'Villa de prueba'): Property
{
    $p = Property::factory()->published()->create(array_merge([
        'property_type_id' => PropertyType::first()->id,
    ], $atributos));

    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => $titulo]]);

    return $p->fresh();
}

/*
|--------------------------------------------------------------------------
| La rejilla de especificaciones
|--------------------------------------------------------------------------
| Antes se pintaban siempre las tres con un guion cuando faltaba el dato. Eso
| daba dos resultados malos: un solar anunciando «— Habs — Baños», que no
| tiene sentido ni aunque el dato estuviera, y un catálogo lleno de guiones
| que parece a medio cargar.
*/

it('no pinta la fila de especificaciones cuando no hay ningun dato', function () {
    fichaConDatos(['bedrooms' => null, 'bathrooms' => null,
        'construction_area' => null, 'land_area' => null, 'parking_spaces' => null]);

    $html = $this->get('/propiedades')->assertOk()->getContent();

    expect($html)->not->toContain('— Habs')
        ->and($html)->not->toContain('material-symbols-outlined mb-1 text-[20px] text-outline">bed');
});

it('pinta solo las especificaciones que existen', function () {
    fichaConDatos(['bedrooms' => 3, 'bathrooms' => null,
        'construction_area' => 210, 'land_area' => null, 'parking_spaces' => null]);

    $html = $this->get('/propiedades')->getContent();

    expect($html)->toContain('3 Habs')
        ->and($html)->toContain('210 m²')
        ->and($html)->not->toContain('Baños</dt>');
});

it('un terreno muestra la superficie del terreno, no la construida', function () {
    // En un solar la cifra que importa es el terreno; la construida es cero.
    fichaConDatos(['bedrooms' => null, 'bathrooms' => null,
        'construction_area' => null, 'land_area' => 19000, 'parking_spaces' => null]);

    expect($this->get('/propiedades')->getContent())->toContain('19,000 m²');
});

/**
 * La superficie separa los miles igual que el precio que aparece encima, en
 * la misma tarjeta. Antes usaba el convenio contrario —«19.000 m²» junto a
 * «US$ 180,000»— y en ingles esa cifra se lee como diecinueve.
 */
it('la superficie y el precio usan el mismo separador de miles', function () {
    fichaConDatos(['price' => 180000, 'construction_area' => 19000]);

    foreach (['es' => '/propiedades', 'en' => '/en/properties'] as $ruta) {
        $html = $this->get($ruta)->assertOk()->getContent();

        expect($html)->toContain('19,000 m²')
            ->and($html)->toContain('180,000')
            ->and($html)->not->toContain('19.000 m²');
    }
});

/**
 * Las etiquetas de la rejilla van bajo un icono, en una columna estrecha y
 * de ancho fijo. Si una crece —«Parking space»— se derrama sobre la vecina.
 */
it('las etiquetas cortas en ingles caben en su columna', function () {
    fichaConDatos(['bathrooms' => 1.5, 'parking_spaces' => 1, 'construction_area' => 80]);

    $html = $this->get('/en/properties')->assertOk()->getContent();

    expect($html)->toContain('1 Parking')
        ->and($html)->not->toContain('Parking space')
        ->and($html)->not->toContain('whitespace-nowrap text-label-md');
});

/*
|--------------------------------------------------------------------------
| Peso de las imágenes
|--------------------------------------------------------------------------
*/

it('la tarjeta usa la miniatura, no la foto completa', function () {
    // Medido en el navegador: la tarjeta muestra 284 px y descargaba la de
    // 1024. Doce tarjetas sumaban 1 MB solo en imágenes.
    $p = fichaConDatos();

    $imagen = PropertyImage::factory()->create([
        'property_id' => $p->id,
        'is_main' => true,
        'path' => 'properties/1/completa.webp',
        'thumbnail_path' => 'properties/1/completa-thumb.webp',
        'webp_path' => 'properties/1/completa.webp',
    ]);

    $html = $this->get('/propiedades')->getContent();

    expect($html)->toContain($imagen->thumbnailUrl())
        ->and($html)->not->toContain('src="'.$imagen->url().'"');
});

it('toda imagen de tarjeta declara width y height', function () {
    // Sin ellos el layout salta al cargar. Medido: CLS 0,0000.
    $p = fichaConDatos();
    PropertyImage::factory()->create(['property_id' => $p->id, 'is_main' => true]);

    $html = $this->get('/propiedades')->getContent();

    preg_match_all('/<img[^>]*class="[^"]*object-cover[^"]*"[^>]*>/', $html, $m);

    expect($m[0])->not->toBeEmpty();

    foreach ($m[0] as $img) {
        expect($img)->toContain('width=')->toContain('height=');
    }
});

/*
|--------------------------------------------------------------------------
| N+1
|--------------------------------------------------------------------------
*/

it('el listado no lanza una consulta por tarjeta', function () {
    // PropertyImage::altText() cae al título de la propiedad, y sin la
    // relación inversa cargada eso eran DOS consultas por imagen: el listado
    // pasaba de 11 a 35 consultas con solo 12 tarjetas, y habría seguido
    // creciendo. Lo resuelve chaperone() en las relaciones de Property.
    foreach (range(1, 12) as $i) {
        $p = fichaConDatos(titulo: "Propiedad {$i}");
        PropertyImage::factory()->create(['property_id' => $p->id, 'is_main' => true]);
    }

    $this->get('/propiedades');   // calienta

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get('/propiedades')->assertOk();
    $consultas = count(DB::getQueryLog());
    DB::disableQueryLog();

    // El margen es amplio a propósito: lo que se vigila no es la cifra exacta
    // sino que NO crezca con el número de tarjetas.
    expect($consultas)->toBeLessThan(20);
});
