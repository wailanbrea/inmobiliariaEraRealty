<?php

use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Modules\Leads\Models\Lead;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();

    $this->tipo = PropertyType::first();
    $this->service = app(PropertyService::class);
});

/** Crea una propiedad publicada con traducciones. */
function propiedadPublicada(array $atributos = [], ?string $titulo = null): Property
{
    $property = Property::factory()->published()->create(array_merge([
        'property_type_id' => PropertyType::first()->id,
    ], $atributos));

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => $titulo ?? 'Villa de prueba', 'description' => 'Descripción larga.'],
        'en' => ['title' => ($titulo ?? 'Test villa').' EN', 'description' => 'Long description.'],
    ]);

    return $property->fresh(['translations']);
}

/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/

it('muestra el inicio con los textos editables', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Encuentra tu próxima propiedad', escape: false);
});

it('usa el texto del panel y no el del codigo', function () {
    $seccion = ContentSection::where('section_key', 'hero')->first();
    $seccion->translations()->where('locale', 'es')->update(['title' => 'Titular cambiado desde el panel']);
    ContentSection::flushCache('home');

    $this->get('/')->assertSee('Titular cambiado desde el panel', escape: false);
});

it('muestra el inicio en ingles', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Find your next property', escape: false);
});

it('solo lista propiedades destacadas y publicadas en el inicio', function () {
    propiedadPublicada(['is_featured' => true], 'Destacada visible');
    propiedadPublicada(['is_featured' => false], 'No destacada');

    Property::factory()->draft()->create([
        'property_type_id' => $this->tipo->id,
        'is_featured' => true,
    ]);

    $respuesta = $this->get('/');

    $respuesta->assertSee('Destacada visible', escape: false);
    $respuesta->assertDontSee('No destacada', escape: false);
});

/*
|--------------------------------------------------------------------------
| Listado
|--------------------------------------------------------------------------
*/

it('muestra el listado de propiedades', function () {
    propiedadPublicada([], 'Apartamento en Piantini');

    $this->get('/propiedades')
        ->assertOk()
        ->assertSee('Apartamento en Piantini', escape: false);
});

it('busca por titulo codigo y ubicacion', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::where('slug', 'la-vega')->first();

    propiedadPublicada([
        'province_id' => $provincia->id,
        'address' => 'Jarabacoa',
    ], 'Propiedad de montaña');

    $this->get('/propiedades?q=Jarabacoa')
        ->assertSee('Propiedad de montaña', escape: false);
});

it('no muestra borradores en el listado', function () {
    $borrador = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);
    $this->service->syncTranslations($borrador, ['es' => ['title' => 'Borrador secreto']]);

    $this->get('/propiedades')->assertDontSee('Borrador secreto', escape: false);
});

it('no muestra propiedades programadas para el futuro', function () {
    $futura = Property::factory()->create([
        'property_type_id' => $this->tipo->id,
        'status' => PropertyStatus::Available,
        'published_at' => now()->addWeek(),
    ]);
    $this->service->syncTranslations($futura, ['es' => ['title' => 'Programada']]);

    $this->get('/propiedades')->assertDontSee('Programada', escape: false);
});

it('filtra por operacion', function () {
    propiedadPublicada(['operation_type' => OperationType::Sale], 'En venta aqui');
    propiedadPublicada(['operation_type' => OperationType::Rent], 'En alquiler aqui');

    $this->get('/propiedades?operacion=rent')
        ->assertSee('En alquiler aqui', escape: false)
        ->assertDontSee('En venta aqui', escape: false);
});

it('filtra por tipo de propiedad', function () {
    $villa = PropertyType::where('slug', 'villa')->first();
    $casa = PropertyType::where('slug', 'casa')->first();

    propiedadPublicada(['property_type_id' => $villa->id], 'Una villa');
    propiedadPublicada(['property_type_id' => $casa->id], 'Una casa');

    $this->get('/propiedades?tipo=villa')
        ->assertSee('Una villa', escape: false)
        ->assertDontSee('Una casa', escape: false);
});

it('filtra por provincia', function () {
    $this->seed(LocationSeeder::class);

    $dn = Province::where('slug', 'distrito-nacional')->first();
    $altagracia = Province::where('slug', 'la-altagracia')->first();

    propiedadPublicada(['province_id' => $dn->id], 'En la capital');
    propiedadPublicada(['province_id' => $altagracia->id], 'En Punta Cana');

    $this->get('/propiedades?provincia=la-altagracia')
        ->assertSee('En Punta Cana', escape: false)
        ->assertDontSee('En la capital', escape: false);
});

it('filtra por sector y permite combinarlo con la provincia', function () {
    $this->seed(LocationSeeder::class);

    $provincia = Province::where('slug', 'la-vega')->first();
    $ciudad = City::where('province_id', $provincia->id)->first();
    $sector = Sector::where('city_id', $ciudad->id)->first();

    propiedadPublicada([
        'province_id' => $provincia->id,
        'city_id' => $ciudad->id,
        'sector_id' => $sector->id,
    ], 'En '.$sector->name);
    propiedadPublicada([], 'Fuera del sector');

    $this->get('/propiedades?provincia='.$provincia->slug.'&sector='.$sector->id)
        ->assertSee('En '.$sector->name, escape: false)
        ->assertDontSee('Fuera del sector', escape: false);
});

it('filtra por rango de precio', function () {
    // Titulos distintivos a proposito: un titulo corto como "Cara" es
    // subcadena de "Características", que aparece en los filtros, y haria
    // pasar o fallar la prueba por accidente.
    propiedadPublicada(['price' => 100000, 'currency' => 'USD'], 'Estudio economico');
    propiedadPublicada(['price' => 900000, 'currency' => 'USD'], 'Mansion costosa');

    $this->get('/propiedades?precio_max=200000&moneda=USD')
        ->assertSee('Estudio economico', escape: false)
        ->assertDontSee('Mansion costosa', escape: false);
});

it('filtra por habitaciones como minimo', function () {
    propiedadPublicada(['bedrooms' => 2], 'Dos habitaciones');
    propiedadPublicada(['bedrooms' => 4], 'Cuatro habitaciones');

    $this->get('/propiedades?habitaciones=3')
        ->assertSee('Cuatro habitaciones', escape: false)
        ->assertDontSee('Dos habitaciones', escape: false);
});

it('exige TODAS las amenidades marcadas, no cualquiera', function () {
    $this->seed(AmenitySeeder::class);

    $piscina = Amenity::where('slug', 'piscina')->first();
    $gimnasio = Amenity::where('slug', 'gimnasio')->first();

    $ambas = propiedadPublicada([], 'Con piscina y gimnasio');
    $ambas->amenities()->sync([$piscina->id, $gimnasio->id]);

    $solaUna = propiedadPublicada([], 'Solo con piscina');
    $solaUna->amenities()->sync([$piscina->id]);

    $this->get('/propiedades?amenidades[]=piscina&amenidades[]=gimnasio')
        ->assertSee('Con piscina y gimnasio', escape: false)
        ->assertDontSee('Solo con piscina', escape: false);
});

it('ordena por precio ascendente', function () {
    propiedadPublicada(['price' => 500000], 'La cara');
    propiedadPublicada(['price' => 100000], 'La barata');

    $html = $this->get('/propiedades?orden=price_asc')->getContent();

    expect(strpos($html, 'La barata'))->toBeLessThan(strpos($html, 'La cara'));
});

it('conserva los filtros al paginar', function () {
    Property::factory()->published()->count(15)->create([
        'property_type_id' => $this->tipo->id,
        'operation_type' => OperationType::Rent,
    ])->each(fn ($p) => app(PropertyService::class)->syncTranslations($p, [
        'es' => ['title' => 'Alquiler '.$p->id],
    ]));

    $this->get('/propiedades?operacion=rent')
        ->assertOk()
        ->assertSee('operacion=rent', escape: false);
});

it('muestra nueve propiedades por pagina', function () {
    Property::factory()->published()->count(10)->create([
        'property_type_id' => $this->tipo->id,
    ])->each(fn ($p) => app(PropertyService::class)->syncTranslations($p, [
        'es' => ['title' => 'Pagina '.$p->id],
    ]));

    $respuesta = $this->get('/propiedades');

    expect($respuesta->viewData('properties')->count())->toBe(9)
        ->and($respuesta->viewData('properties')->perPage())->toBe(9);
});

it('marca noindex el listado filtrado', function () {
    // Miles de URL casi duplicadas destrozarian el presupuesto de rastreo.
    $this->get('/propiedades')->assertDontSee('noindex', escape: false);
    $this->get('/propiedades?operacion=rent')->assertSee('noindex', escape: false);
});

it('muestra un estado vacio util cuando no hay resultados', function () {
    $this->get('/propiedades?q=inexistente')
        ->assertOk()
        ->assertSee('No encontramos propiedades', escape: false);
});

/*
|--------------------------------------------------------------------------
| Detalle
|--------------------------------------------------------------------------
*/

it('muestra el detalle de una propiedad publicada', function () {
    $property = propiedadPublicada([], 'Villa Cap Cana');

    $this->get('/propiedades/villa-cap-cana')
        ->assertOk()
        ->assertSee('Villa Cap Cana', escape: false)
        ->assertSee($property->reference_code, escape: false);
});

it('devuelve 404 para una propiedad en borrador', function () {
    $borrador = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);
    $this->service->syncTranslations($borrador, ['es' => ['title' => 'Borrador oculto']]);

    $this->get('/propiedades/borrador-oculto')->assertNotFound();
});

it('deja ver un borrador con enlace firmado de vista previa', function () {
    $borrador = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);
    $this->service->syncTranslations($borrador, ['es' => ['title' => 'Borrador en preview']]);

    $url = URL::temporarySignedRoute('es.properties.show', now()->addMinutes(30), [
        'slug' => 'borrador-en-preview',
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Vista previa', escape: false);
});

it('devuelve 404 con una firma caducada', function () {
    $borrador = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);
    $this->service->syncTranslations($borrador, ['es' => ['title' => 'Caducado']]);

    $url = URL::temporarySignedRoute('es.properties.show', now()->subMinute(), ['slug' => 'caducado']);

    $this->get($url)->assertNotFound();
});

it('resuelve el detalle por el slug de cada idioma', function () {
    // El helper genera el titulo ingles anadiendo ' EN'.
    propiedadPublicada([], 'Villa bilingue');

    $this->get('/propiedades/villa-bilingue')->assertOk();
    $this->get('/en/properties/villa-bilingue-en')->assertOk();
});

it('publica los datos estructurados de la propiedad', function () {
    propiedadPublicada(['price' => 450000], 'Con schema');

    $this->get('/propiedades/con-schema')
        ->assertSee('RealEstateListing', escape: false)
        ->assertSee('"price":450000', escape: false);
});

it('no publica coordenadas si la ubicacion es aproximada', function () {
    propiedadPublicada([
        'latitude' => 18.4861,
        'longitude' => -69.9312,
        'show_exact_location' => false,
    ], 'Sin coordenadas');

    $this->get('/propiedades/sin-coordenadas')
        ->assertDontSee('GeoCoordinates', escape: false)
        ->assertDontSee('18.4861', escape: false);
});

it('publica coordenadas cuando se autorizan', function () {
    propiedadPublicada([
        'latitude' => 18.4861,
        'longitude' => -69.9312,
        'show_exact_location' => true,
    ], 'Con coordenadas');

    $this->get('/propiedades/con-coordenadas')->assertSee('GeoCoordinates', escape: false);
});

it('muestra la amenidad linea blanca en el detalle', function () {
    $this->seed(AmenitySeeder::class);
    $property = propiedadPublicada([], 'Con linea blanca');
    $lineaBlanca = Amenity::where('slug', 'linea-blanca')->firstOrFail();
    $property->amenities()->attach($lineaBlanca);

    $this->get('/propiedades/con-linea-blanca')
        ->assertSee('Línea blanca', escape: false);
});

it('marca noindex una propiedad vendida', function () {
    propiedadPublicada(['status' => PropertyStatus::Sold], 'Ya vendida');

    // Sigue visible como prueba social, pero no debe competir en Google.
    $this->get('/propiedades/ya-vendida')
        ->assertOk()
        ->assertSee('noindex', escape: false);
});

it('no ofrece contacto en una propiedad vendida', function () {
    propiedadPublicada(['status' => PropertyStatus::Sold], 'Vendida sin contacto');

    $this->get('/propiedades/vendida-sin-contacto')
        ->assertSee('ya no está disponible', escape: false);
});

it('genera el enlace de whatsapp con la referencia de la propiedad', function () {
    $property = propiedadPublicada([], 'Con whatsapp');

    $this->get('/propiedades/con-whatsapp')
        ->assertSee('wa.me', escape: false)
        ->assertSee(rawurlencode($property->reference_code), escape: false);
});

it('cuenta la visita una sola vez por sesion', function () {
    $property = propiedadPublicada([], 'Contador');

    $this->get('/propiedades/contador');
    $this->get('/propiedades/contador');

    expect($property->fresh()->views_count)->toBe(1);
});

it('no cuenta visitas de una vista previa', function () {
    $borrador = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);
    $this->service->syncTranslations($borrador, ['es' => ['title' => 'Preview sin contar']]);

    $url = URL::temporarySignedRoute('es.properties.show', now()->addMinutes(30), [
        'slug' => 'preview-sin-contar',
    ]);

    $this->get($url);

    expect($borrador->fresh()->views_count)->toBe(0);
});

it('muestra las imagenes con dimensiones para evitar el salto de layout', function () {
    $property = propiedadPublicada([], 'Con fotos');
    PropertyImage::factory()->main()->create([
        'property_id' => $property->id,
        'width' => 1200,
        'height' => 800,
    ]);

    $this->get('/propiedades/con-fotos')
        ->assertSee('width="1200"', escape: false)
        ->assertSee('height="800"', escape: false);
});

it('no deja buffers de salida abiertos al renderizar', function () {
    // @section('x', null) hace que Blade abra un ob_start() que nunca cierra.
    // Paso con una propiedad sin descripción corta ni meta propia.
    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => 'Sin descripcion alguna'],   // sin short_description ni meta
    ]);

    $nivelInicial = ob_get_level();

    $this->get('/propiedades/sin-descripcion-alguna')->assertOk();

    expect(ob_get_level())->toBe($nivelInicial);
});

it('muestra propiedades similares del mismo tipo y ciudad', function () {
    $this->seed(LocationSeeder::class);
    $ciudad = City::where('slug', 'santo-domingo')->first();

    $principal = propiedadPublicada([
        'city_id' => $ciudad->id, 'price' => 300000,
    ], 'Principal');

    propiedadPublicada([
        'city_id' => $ciudad->id, 'price' => 320000,
    ], 'Similar cercana');

    $this->get('/propiedades/principal')->assertSee('Similar cercana', escape: false);
});

/*
|--------------------------------------------------------------------------
| Datos de contacto en el pie
|--------------------------------------------------------------------------
*/

it('el listado y el detalle usan la configuracion del sitio', function () {
    app(SettingsService::class)->set('site_name', 'Inmobiliaria XYZ');
    propiedadPublicada([], 'Cualquiera');

    $this->get('/propiedades')->assertSee('Inmobiliaria XYZ', escape: false);
    $this->get('/propiedades/cualquiera')->assertSee('Inmobiliaria XYZ', escape: false);
});

it('guarda una consulta desde el detalle de propiedad', function () {
    $property = propiedadPublicada([], 'Villa consultada');
    $slug = $property->translation('es')->first()->slug;

    $this->post('/propiedades/'.$slug, [
        'name' => 'Cliente detalle',
        'phone' => '8095550101',
        'email' => 'cliente@example.com',
        'message' => 'Quiero coordinar una visita a esta propiedad.',
        'form_token' => Crypt::encryptString((string) now()->subSeconds(4)->timestamp),
    ])->assertRedirect('/propiedades/'.$slug);

    $lead = Lead::firstOrFail();
    expect($lead->property_id)->toBe($property->id)
        ->and($lead->source->value)->toBe('property_detail');
});

it('no acepta consultas para una propiedad no publicada', function () {
    $property = propiedadPublicada();
    $slug = $property->translation('es')->first()->slug;
    $property->update(['status' => PropertyStatus::Sold]);

    $this->post('/propiedades/'.$slug, ['name' => 'Cliente', 'phone' => '8095550101', 'message' => 'Consulta suficientemente larga', 'form_token' => Crypt::encryptString((string) now()->subSeconds(4)->timestamp)])->assertNotFound();
});
