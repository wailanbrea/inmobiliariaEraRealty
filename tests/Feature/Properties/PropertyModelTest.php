<?php

use App\Enums\Currency;
use App\Enums\PricePeriod;
use App\Enums\PropertyStatus;
use App\Modules\Locations\Models\Sector;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->service = app(PropertyService::class);
});

/*
|--------------------------------------------------------------------------
| Codigo de referencia
|--------------------------------------------------------------------------
*/

it('genera codigos de referencia correlativos', function () {
    expect($this->service->nextReferenceCode())->toBe('ERA-1001');

    Property::factory()->create(['reference_code' => 'ERA-1001']);
    expect($this->service->nextReferenceCode())->toBe('ERA-1002');

    Property::factory()->create(['reference_code' => 'ERA-1050']);
    expect($this->service->nextReferenceCode())->toBe('ERA-1051');
});

it('no repite el codigo aunque se borre una propiedad', function () {
    // Contar filas en vez de mirar el maximo generaria un codigo repetido.
    $p = Property::factory()->create(['reference_code' => 'ERA-1001']);
    Property::factory()->create(['reference_code' => 'ERA-1002']);

    $p->delete();   // soft delete

    expect($this->service->nextReferenceCode())->toBe('ERA-1003');
});

/*
|--------------------------------------------------------------------------
| Traducciones
|--------------------------------------------------------------------------
*/

it('guarda titulo y slug distintos por idioma', function () {
    $property = Property::factory()->create();

    $this->service->syncTranslations($property, [
        'es' => ['title' => 'Villa frente al mar en Cap Cana'],
        'en' => ['title' => 'Beachfront villa in Cap Cana'],
    ]);

    $property->refresh();

    app()->setLocale('es');
    expect($property->title)->toBe('Villa frente al mar en Cap Cana')
        ->and($property->slug)->toBe('villa-frente-al-mar-en-cap-cana');

    app()->setLocale('en');
    expect($property->fresh()->title)->toBe('Beachfront villa in Cap Cana')
        ->and($property->fresh()->slug)->toBe('beachfront-villa-in-cap-cana');
});

it('permite el mismo slug en idiomas distintos', function () {
    // /propiedades/villa-cap-cana y /en/properties/villa-cap-cana no chocan.
    $a = Property::factory()->create();
    $b = Property::factory()->create();

    $this->service->syncTranslations($a, ['es' => ['title' => 'Villa Cap Cana']]);
    $this->service->syncTranslations($b, ['en' => ['title' => 'Villa Cap Cana']]);

    expect($a->translations()->where('locale', 'es')->value('slug'))->toBe('villa-cap-cana')
        ->and($b->translations()->where('locale', 'en')->value('slug'))->toBe('villa-cap-cana');
});

it('desambigua slugs repetidos dentro del mismo idioma', function () {
    $a = Property::factory()->create();
    $b = Property::factory()->create();

    $this->service->syncTranslations($a, ['es' => ['title' => 'Villa Cap Cana']]);
    $this->service->syncTranslations($b, ['es' => ['title' => 'Villa Cap Cana']]);

    expect($b->translations()->where('locale', 'es')->value('slug'))->toBe('villa-cap-cana-2');
});

it('cae al espanol cuando falta la traduccion inglesa', function () {
    $property = Property::factory()->spanishOnly('Apartamento en Piantini')->create();

    app()->setLocale('en');

    // Mejor una ficha en espanol que una ficha en blanco.
    expect($property->fresh()->title)->toBe('Apartamento en Piantini');
});

it('sabe que traducciones le faltan', function () {
    $property = Property::factory()->spanishOnly()->create();

    expect($this->service->missingTranslations($property->fresh()))->toBe(['en']);
});

it('ignora un idioma sin titulo en vez de crear una ficha vacia', function () {
    $property = Property::factory()->create();

    $this->service->syncTranslations($property, [
        'es' => ['title' => 'Con título'],
        'en' => ['title' => '', 'description' => 'Sólo descripción'],
    ]);

    expect($property->translations()->count())->toBe(1);
});

it('conserva el slug al editar el titulo de una propiedad publicada', function () {
    // Cambiar la URL de una ficha indexada tira su posicionamiento.
    $property = Property::factory()->published()->create();
    $this->service->syncTranslations($property, ['es' => ['title' => 'Título original']]);

    $slugOriginal = $property->translations()->where('locale', 'es')->value('slug');

    $this->service->syncTranslations($property, ['es' => ['title' => 'Título corregido']]);

    expect($property->translations()->where('locale', 'es')->value('slug'))->toBe($slugOriginal)
        ->and($property->fresh()->title)->toBe('Título corregido');
});

it('permite cambiar el slug a mano', function () {
    $property = Property::factory()->create();
    $this->service->syncTranslations($property, ['es' => ['title' => 'Título original']]);

    $this->service->syncTranslations($property, [
        'es' => ['title' => 'Título original', 'slug' => 'slug-elegido-a-mano'],
    ]);

    expect($property->translations()->where('locale', 'es')->value('slug'))
        ->toBe('slug-elegido-a-mano');
});

/*
|--------------------------------------------------------------------------
| Estados
|--------------------------------------------------------------------------
*/

it('solo muestra en publico las propiedades publicadas', function () {
    Property::factory()->published()->count(3)->create();
    Property::factory()->draft()->count(2)->create();
    Property::factory()->create([
        'status' => PropertyStatus::Available,
        'published_at' => now()->addWeek(),   // programada
    ]);

    expect(Property::published()->count())->toBe(3);
});

it('publicar sella la fecha de publicacion', function () {
    $property = Property::factory()->draft()->create();

    $this->service->publish($property);

    expect($property->fresh()->status)->toBe(PropertyStatus::Available)
        ->and($property->fresh()->published_at)->not->toBeNull();
});

it('no pisa la fecha de publicacion al republicar', function () {
    // Pausar y volver a publicar no debe falsear la antiguedad de la ficha.
    $original = now()->subMonths(3);
    $property = Property::factory()->create([
        'status' => PropertyStatus::Available,
        'published_at' => $original,
    ]);

    $this->service->pause($property);
    $this->service->publish($property->fresh());

    expect($property->fresh()->published_at->toDateString())->toBe($original->toDateString());
});

it('sella la fecha al pasar de borrador a vendida directamente', function () {
    $property = Property::factory()->draft()->create();

    $this->service->changeStatus($property, PropertyStatus::Sold);

    expect($property->fresh()->published_at)->not->toBeNull();
});

it('distingue que estados aceptan contacto', function () {
    expect(PropertyStatus::Available->acceptsLeads())->toBeTrue()
        ->and(PropertyStatus::Reserved->acceptsLeads())->toBeTrue()
        ->and(PropertyStatus::Sold->acceptsLeads())->toBeFalse()
        ->and(PropertyStatus::Draft->acceptsLeads())->toBeFalse();
});

it('solo indexa en Google los estados que corresponden', function () {
    expect(PropertyStatus::Available->isIndexable())->toBeTrue()
        ->and(PropertyStatus::Sold->isIndexable())->toBeFalse()
        ->and(PropertyStatus::Draft->isIndexable())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Precio y moneda
|--------------------------------------------------------------------------
*/

it('formatea el precio con simbolo y separadores', function () {
    $property = Property::factory()->create([
        'price' => 450000,
        'currency' => Currency::USD,
    ]);

    expect($property->formattedPrice())->toBe('US$ 450,000');
});

it('anade el periodo en los alquileres', function () {
    app()->setLocale('es');

    $property = Property::factory()->create([
        'price' => 1200,
        'currency' => Currency::USD,
        'price_period' => PricePeriod::Month,
    ]);

    expect($property->formattedPrice())->toBe('US$ 1,200 /mes');
});

it('muestra precio a consultar cuando no hay precio', function () {
    app()->setLocale('es');

    $property = Property::factory()->create(['price' => null]);

    expect($property->formattedPrice())->toBe('Precio a consultar');
});

it('convierte el precio a la otra moneda con la tasa configurada', function () {
    $this->seed(SettingsSeeder::class);
    app(SettingsService::class)->flush();
    app(SettingsService::class)->set('currency_usd_to_dop', '60');

    $property = Property::factory()->create([
        'price' => 100000,
        'currency' => Currency::USD,
    ]);

    expect($property->priceInOtherCurrency())->toBe('RD$ 6,000,000');
});

it('no inventa una conversion si no hay tasa', function () {
    $this->seed(SettingsSeeder::class);
    app(SettingsService::class)->flush();
    app(SettingsService::class)->set('currency_usd_to_dop', '0');

    $property = Property::factory()->create(['price' => 100000, 'currency' => Currency::USD]);

    expect($property->priceInOtherCurrency())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Ubicacion y privacidad
|--------------------------------------------------------------------------
*/

it('no expone coordenadas si la ubicacion exacta esta oculta', function () {
    $property = Property::factory()->create([
        'latitude' => 18.4861,
        'longitude' => -69.9312,
        'show_exact_location' => false,
    ]);

    expect($property->publicCoordinates())->toBeNull();
});

it('expone coordenadas cuando el administrador lo autoriza', function () {
    $property = Property::factory()->create([
        'latitude' => 18.4861,
        'longitude' => -69.9312,
        'show_exact_location' => true,
    ]);

    expect($property->publicCoordinates())->toBe(['lat' => 18.4861, 'lng' => -69.9312]);
});

it('oculta los datos del propietario al serializar', function () {
    $property = Property::factory()->create([
        'owner_name' => 'Juan Pérez',
        'owner_phone' => '8095551234',
        'internal_notes' => 'Negociable hasta 400k',
    ]);

    $json = $property->toArray();

    expect($json)->not->toHaveKey('owner_name')
        ->and($json)->not->toHaveKey('owner_phone')
        ->and($json)->not->toHaveKey('internal_notes');
});

it('arma la etiqueta de ubicacion como en el diseno', function () {
    // El diseno muestra "Piantini, Santo Domingo": sector y ciudad.
    $this->seed(LocationSeeder::class);

    $sector = Sector::where('slug', 'piantini')->first();

    $property = Property::factory()->create([
        'province_id' => $sector->city->province_id,
        'city_id' => $sector->city_id,
        'sector_id' => $sector->id,
    ]);

    expect($property->fresh()->locationLabel())->toBe('Piantini, Santo Domingo');
});

it('cae a ciudad y provincia cuando la propiedad no tiene sector', function () {
    $property = Property::factory()->inSantoDomingo()->create();

    expect($property->fresh()->locationLabel())->toBe('Santo Domingo, Distrito Nacional');
});

/*
|--------------------------------------------------------------------------
| Catalogos traducibles
|--------------------------------------------------------------------------
*/

it('traduce los nombres de los tipos de propiedad', function () {
    $this->seed(PropertyTypeSeeder::class);

    $solar = PropertyType::where('slug', 'solar')->first();

    app()->setLocale('es');
    expect($solar->fresh()->name)->toBe('Solar');

    app()->setLocale('en');
    expect($solar->fresh()->name)->toBe('Lot');
});
