<?php

use App\Models\User;
use App\Modules\Locations\Livewire\LocationManager;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyTypes\Livewire\CatalogManager;
use App\Modules\PropertyTypes\Models\PropertyType;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = userWithRole('admin');
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para el catalogo', function (string $ruta) {
    $this->get($ruta)->assertRedirect(route('admin.login'));
})->with([
    '/admin/catalogo/tipos',
    '/admin/catalogo/amenidades',
    '/admin/catalogo/ubicaciones',
]);

it('muestra las tres pantallas de catalogo', function (string $ruta) {
    $this->actingAs($this->admin)->get($ruta)->assertOk();
})->with([
    '/admin/catalogo/tipos',
    '/admin/catalogo/amenidades',
    '/admin/catalogo/ubicaciones',
]);

/*
|--------------------------------------------------------------------------
| Tipos y amenidades
|--------------------------------------------------------------------------
*/

it('crea un tipo de propiedad en ambos idiomas', function () {
    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->set('name.es', 'Chalet')
        ->set('name.en', 'Chalet house')
        ->set('icon', 'cottage')
        ->call('save')
        ->assertHasNoErrors();

    $tipo = PropertyType::where('slug', 'chalet')->first();

    expect($tipo)->not->toBeNull()
        ->and($tipo->getTranslation('name', 'es'))->toBe('Chalet')
        ->and($tipo->getTranslation('name', 'en'))->toBe('Chalet house');
});

it('exige el nombre en espanol', function () {
    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->set('name.es', '')
        ->set('name.en', 'Only English')
        ->call('save')
        ->assertHasErrors('name.es');

    expect(PropertyType::count())->toBe(0);
});

it('genera el slug desde el nombre en espanol', function () {
    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->set('name.es', 'Local Comercial Grande')
        ->set('name.en', 'Large Retail Space')
        ->call('save');

    // El slug sale del espanol, no del idioma activo de quien guarda.
    expect(PropertyType::first()->slug)->toBe('local-comercial-grande');
});

it('rechaza un slug duplicado', function () {
    $this->seed(PropertyTypeSeeder::class);

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->set('name.es', 'Otra cosa')
        ->set('slug', 'villa')
        ->call('save')
        ->assertHasErrors('slug');
});

it('edita un tipo existente', function () {
    $this->seed(PropertyTypeSeeder::class);
    $villa = PropertyType::where('slug', 'villa')->first();

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->call('edit', $villa->id)
        ->assertSet('name.es', 'Villa')
        ->set('name.en', 'Luxury Villa')
        ->call('save')
        ->assertHasNoErrors();

    expect($villa->fresh()->getTranslation('name', 'en'))->toBe('Luxury Villa');
});

it('activa y desactiva sin borrar', function () {
    $this->seed(PropertyTypeSeeder::class);
    $villa = PropertyType::where('slug', 'villa')->first();

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->call('toggleActive', $villa->id);

    expect($villa->fresh()->is_active)->toBeFalse()
        ->and(PropertyType::find($villa->id))->not->toBeNull();
});

it('no borra un tipo que estan usando propiedades', function () {
    $this->seed(PropertyTypeSeeder::class);
    $villa = PropertyType::where('slug', 'villa')->first();

    Property::factory()->create(['property_type_id' => $villa->id]);

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->call('delete', $villa->id)
        ->assertSet('errorMessage', fn ($m) => str_contains((string) $m, '1 propiedades'));

    expect(PropertyType::find($villa->id))->not->toBeNull();
});

it('borra un tipo que no usa nadie', function () {
    $this->seed(PropertyTypeSeeder::class);
    $villa = PropertyType::where('slug', 'villa')->first();

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->call('delete', $villa->id);

    expect(PropertyType::find($villa->id))->toBeNull();
});

it('crea una amenidad con categoria', function () {
    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'amenities'])
        ->set('name.es', 'Sala de cine')
        ->set('name.en', 'Home theater')
        ->set('category', 'building')
        ->set('icon', 'theaters')
        ->call('save')
        ->assertHasNoErrors();

    expect(Amenity::where('slug', 'sala-de-cine')->first()?->category)->toBe('building');
});

it('reordena los elementos del catalogo', function () {
    $this->seed(PropertyTypeSeeder::class);

    $primero = PropertyType::orderBy('sort_order')->first();
    $segundo = PropertyType::orderBy('sort_order')->skip(1)->first();

    Livewire::actingAs($this->admin)
        ->test(CatalogManager::class, ['catalog' => 'property-types'])
        ->call('move', $segundo->id, 'up');

    expect($segundo->fresh()->sort_order)->toBeLessThan($primero->fresh()->sort_order);
});

/*
|--------------------------------------------------------------------------
| Ubicaciones
|--------------------------------------------------------------------------
*/

it('anade una provincia', function () {
    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('startAdd', 'province')
        ->set('newName', 'Provincia Nueva')
        ->call('add')
        ->assertHasNoErrors();

    expect(Province::where('slug', 'provincia-nueva')->exists())->toBeTrue();
});

it('anade una ciudad dentro de su provincia', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::where('slug', 'samana')->first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('startAdd', 'city', $provincia->id)
        ->set('newName', 'Ciudad Nueva')
        ->call('add');

    expect(City::where('province_id', $provincia->id)->where('slug', 'ciudad-nueva')->exists())
        ->toBeTrue();
});

it('anade un sector dentro de su ciudad', function () {
    $this->seed(LocationSeeder::class);
    $ciudad = City::where('slug', 'punta-cana')->first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('startAdd', 'sector', $ciudad->id)
        ->set('newName', 'Sector Nuevo')
        ->call('add');

    expect(Sector::where('city_id', $ciudad->id)->where('slug', 'sector-nuevo')->exists())
        ->toBeTrue();
});

it('exige nombre al anadir una ubicacion', function () {
    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('startAdd', 'province')
        ->set('newName', '')
        ->call('add')
        ->assertHasErrors('newName');
});

it('renombra sin cambiar el slug', function () {
    // El slug aparece en las URL de los filtros publicos: cambiarlo rompe
    // los enlaces ya compartidos.
    $this->seed(LocationSeeder::class);
    $provincia = Province::where('slug', 'samana')->first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('startEdit', 'province', $provincia->id, $provincia->name)
        ->set('editName', 'Samaná Corregido')
        ->call('saveEdit');

    expect($provincia->fresh()->name)->toBe('Samaná Corregido')
        ->and($provincia->fresh()->slug)->toBe('samana');
});

it('no borra una provincia que tiene ciudades', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::where('slug', 'la-altagracia')->first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('delete', 'province', $provincia->id)
        ->assertSet('errorMessage', fn ($m) => str_contains((string) $m, 'contiene'));

    expect(Province::find($provincia->id))->not->toBeNull();
});

it('no borra una ciudad usada por propiedades', function () {
    $this->seed(LocationSeeder::class);
    $this->seed(PropertyTypeSeeder::class);

    $ciudad = City::where('slug', 'punta-cana')->first();

    Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'city_id' => $ciudad->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('delete', 'city', $ciudad->id);

    expect(City::find($ciudad->id))->not->toBeNull();
});

it('borra un sector libre', function () {
    $this->seed(LocationSeeder::class);
    $sector = Sector::where('slug', 'cap-cana')->first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('delete', 'sector', $sector->id);

    expect(Sector::find($sector->id))->toBeNull();
});

it('desactiva una ubicacion sin borrarla', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::first();

    Livewire::actingAs($this->admin)
        ->test(LocationManager::class)
        ->call('toggleActive', 'province', $provincia->id);

    expect($provincia->fresh()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Permisos
|--------------------------------------------------------------------------
*/

it('un usuario sin permiso no entra al catalogo', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->get('/admin/catalogo/tipos')->assertForbidden();
});
