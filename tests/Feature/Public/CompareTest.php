<?php

use App\Enums\PropertyStatus;
use App\Modules\Compare\Services\CompareService;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();

    $this->compare = app(CompareService::class);
});

function propiedadComparable(string $titulo, array $atributos = []): Property
{
    $property = Property::factory()->published()->create(array_merge([
        'property_type_id' => PropertyType::first()->id,
    ], $atributos));

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => $titulo],
    ]);

    return $property->fresh(['translations']);
}

/*
|--------------------------------------------------------------------------
| Pagina
|--------------------------------------------------------------------------
*/

it('muestra el comparador vacio con un mensaje util', function () {
    $this->get('/comparar')
        ->assertOk()
        ->assertSee('El comparador está vacío', escape: false);
});

it('no indexa la pagina de comparacion', function () {
    // Cada combinacion generaria una URL distinta sin valor para Google.
    $this->get('/comparar')->assertSee('noindex', escape: false);
});

it('existe en ingles con su segmento traducido', function () {
    $this->get('/en/compare')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Anadir y quitar
|--------------------------------------------------------------------------
*/

it('anade una propiedad al comparador', function () {
    $property = propiedadComparable('Villa comparada');

    $this->from('/propiedades')
        ->post("/comparar/{$property->id}")
        ->assertRedirect('/propiedades');

    expect(app(CompareService::class)->ids())->toBe([$property->id]);
});

it('el boton alterna: segundo clic la quita', function () {
    $property = propiedadComparable('Villa alternada');

    $this->post("/comparar/{$property->id}");
    $this->post("/comparar/{$property->id}");

    expect(app(CompareService::class)->count())->toBe(0);
});

it('no admite mas de cuatro propiedades', function () {
    $propiedades = collect(range(1, 5))->map(fn ($i) => propiedadComparable("Propiedad {$i}"));

    foreach ($propiedades as $p) {
        $this->post("/comparar/{$p->id}");
    }

    expect(app(CompareService::class)->count())->toBe(CompareService::MAX);
});

it('avisa cuando el comparador esta lleno', function () {
    foreach (range(1, CompareService::MAX) as $i) {
        $this->post('/comparar/'.propiedadComparable("Llena {$i}")->id);
    }

    $sobrante = propiedadComparable('La que sobra');

    $this->from('/propiedades')
        ->post("/comparar/{$sobrante->id}")
        ->assertSessionHas('compare_error');
});

it('quita una propiedad concreta', function () {
    $a = propiedadComparable('Se queda');
    $b = propiedadComparable('Se va');

    $this->post("/comparar/{$a->id}");
    $this->post("/comparar/{$b->id}");

    $this->post("/comparar/{$b->id}/quitar");

    expect(app(CompareService::class)->ids())->toBe([$a->id]);
});

it('vacia el comparador entero', function () {
    $this->post('/comparar/'.propiedadComparable('Una')->id);
    $this->post('/comparar/'.propiedadComparable('Otra')->id);

    $this->post('/comparar-vaciar');

    expect(app(CompareService::class)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Tabla comparativa
|--------------------------------------------------------------------------
*/

it('muestra las propiedades comparadas', function () {
    $a = propiedadComparable('Villa Cap Cana', ['price' => 500000]);
    $b = propiedadComparable('Apartamento Piantini', ['price' => 250000]);

    $this->post("/comparar/{$a->id}");
    $this->post("/comparar/{$b->id}");

    $this->get('/comparar')
        ->assertOk()
        ->assertSee('Villa Cap Cana', escape: false)
        ->assertSee('Apartamento Piantini', escape: false)
        ->assertSee('US$ 500,000', escape: false)
        ->assertSee('US$ 250,000', escape: false);
});

it('detecta que filas difieren', function () {
    $a = propiedadComparable('A', ['bedrooms' => 2, 'parking_spaces' => 1]);
    $b = propiedadComparable('B', ['bedrooms' => 4, 'parking_spaces' => 1]);

    $servicio = app(CompareService::class);
    $servicio->add($a->id);
    $servicio->add($b->id);

    $diferencias = $servicio->differences($servicio->properties());

    expect($diferencias['bedrooms'])->toBeTrue()
        ->and($diferencias['parking'])->toBeFalse();
});

it('lista la union de amenidades de todas', function () {
    $this->seed(AmenitySeeder::class);

    $piscina = Amenity::where('slug', 'piscina')->first();
    $gimnasio = Amenity::where('slug', 'gimnasio')->first();

    $a = propiedadComparable('Con piscina');
    $a->amenities()->sync([$piscina->id]);

    $b = propiedadComparable('Con gimnasio');
    $b->amenities()->sync([$gimnasio->id]);

    $servicio = app(CompareService::class);
    $servicio->add($a->id);
    $servicio->add($b->id);

    $union = $servicio->amenityUnion($servicio->properties());

    expect($union->pluck('slug')->all())->toContain('piscina', 'gimnasio');
});

/*
|--------------------------------------------------------------------------
| Enlace compartible
|--------------------------------------------------------------------------
*/

it('carga una comparacion desde el enlace con ids', function () {
    $a = propiedadComparable('Compartida A');
    $b = propiedadComparable('Compartida B');

    $this->get("/comparar?ids={$a->id},{$b->id}")
        ->assertOk()
        ->assertSee('Compartida A', escape: false)
        ->assertSee('Compartida B', escape: false);

    expect(app(CompareService::class)->count())->toBe(2);
});

it('ignora ids inexistentes o no publicados en el enlace', function () {
    $publicada = propiedadComparable('Publicada');
    $borrador = Property::factory()->draft()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    $this->get("/comparar?ids={$publicada->id},{$borrador->id},99999");

    expect(app(CompareService::class)->ids())->toBe([$publicada->id]);
});

it('respeta el limite tambien desde el enlace', function () {
    $ids = collect(range(1, 6))
        ->map(fn ($i) => propiedadComparable("Enlace {$i}")->id)
        ->implode(',');

    $this->get("/comparar?ids={$ids}");

    expect(app(CompareService::class)->count())->toBe(CompareService::MAX);
});

/*
|--------------------------------------------------------------------------
| Integridad
|--------------------------------------------------------------------------
*/

it('saca del comparador lo que deja de estar publicado', function () {
    $property = propiedadComparable('Se despublica');

    app(CompareService::class)->add($property->id);

    $property->update(['status' => PropertyStatus::Draft]);

    // Al pedir las propiedades se limpia sola: el contador no debe mentir.
    expect(app(CompareService::class)->properties())->toHaveCount(0)
        ->and(app(CompareService::class)->count())->toBe(0);
});

it('conserva el orden en que se anadieron', function () {
    $c = propiedadComparable('Tercera');
    $a = propiedadComparable('Primera');
    $b = propiedadComparable('Segunda');

    $servicio = app(CompareService::class);
    $servicio->add($a->id);
    $servicio->add($b->id);
    $servicio->add($c->id);

    expect($servicio->properties()->pluck('id')->all())->toBe([$a->id, $b->id, $c->id]);
});

/*
|--------------------------------------------------------------------------
| Integracion con el listado
|--------------------------------------------------------------------------
*/

it('las tarjetas ofrecen el boton de comparar', function () {
    propiedadComparable('Con boton');

    $this->get('/propiedades')->assertSee('Comparar', escape: false);
});

it('la barra flotante aparece al marcar una propiedad', function () {
    $property = propiedadComparable('Marcada');

    $this->get('/propiedades')->assertDontSee('Ver comparación', escape: false);

    $this->post("/comparar/{$property->id}");

    $this->get('/propiedades')->assertSee('Ver comparación', escape: false);
});

it('la barra flotante no sale en la propia pagina de comparacion', function () {
    $property = propiedadComparable('Sin barra');
    $this->post("/comparar/{$property->id}");

    $this->get('/comparar')->assertDontSee('Ver comparación', escape: false);
});
