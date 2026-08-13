<?php

use App\Enums\OperationType;
use App\Enums\PricePeriod;
use App\Enums\PropertyStatus;
use App\Models\User;
use App\Modules\Agents\Models\Agent;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyTypes\Models\PropertyType;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PropertyTypeSeeder;

beforeEach(function () {
    $this->seed(PropertyTypeSeeder::class);
    $this->admin = userWithRole('admin');
    $this->tipo = PropertyType::first();
});

/** Datos minimos validos para crear una propiedad. */
function datosPropiedad(array $extra = []): array
{
    return array_merge([
        'operation_type' => OperationType::Sale->value,
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Draft->value,
        'currency' => 'USD',
        'translations' => [
            'es' => ['title' => 'Villa de prueba en Cap Cana'],
            'en' => ['title' => 'Test villa in Cap Cana'],
        ],
    ], $extra);
}

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para el listado de propiedades', function () {
    $this->get('/admin/propiedades')->assertRedirect(route('admin.login'));
});

it('muestra el listado al administrador', function () {
    $this->actingAs($this->admin)->get('/admin/propiedades')->assertOk();
});

it('muestra el formulario de creacion', function () {
    $this->actingAs($this->admin)
        ->get('/admin/propiedades/crear')
        ->assertOk()
        ->assertSee('Operación', escape: false);
});

/*
|--------------------------------------------------------------------------
| Crear
|--------------------------------------------------------------------------
*/

it('crea una propiedad con sus dos traducciones', function () {
    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $property = Property::first();

    expect($property)->not->toBeNull()
        ->and($property->reference_code)->toStartWith('ERA-')
        ->and($property->translations)->toHaveCount(2)
        ->and($property->created_by_user_id)->toBe($this->admin->id);

    app()->setLocale('es');
    expect($property->fresh()->title)->toBe('Villa de prueba en Cap Cana');
});

it('exige titulo en espanol', function () {
    $datos = datosPropiedad();
    $datos['translations']['es']['title'] = '';

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', $datos)
        ->assertSessionHasErrors('translations.es.title');

    expect(Property::count())->toBe(0);
});

it('permite crear solo con el idioma por defecto', function () {
    $datos = datosPropiedad();
    unset($datos['translations']['en']);

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', $datos)
        ->assertSessionHasNoErrors();

    expect(Property::first()->translations)->toHaveCount(1);
});

it('crea la propiedad como borrador por defecto', function () {
    $this->actingAs($this->admin)->post('/admin/propiedades', datosPropiedad());

    expect(Property::first()->status)->toBe(PropertyStatus::Draft)
        ->and(Property::first()->published_at)->toBeNull();
});

it('asigna las amenidades marcadas', function () {
    $this->seed(AmenitySeeder::class);
    $ids = Amenity::limit(3)->pluck('id')->all();

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad(['amenities' => $ids]));

    expect(Property::first()->amenities)->toHaveCount(3);
});

/*
|--------------------------------------------------------------------------
| Validacion
|--------------------------------------------------------------------------
*/

it('rechaza una ciudad que no pertenece a la provincia', function () {
    $this->seed(LocationSeeder::class);

    $provinciaA = Province::where('slug', 'distrito-nacional')->first();
    $ciudadDeOtra = City::whereHas('province', fn ($q) => $q->where('slug', 'santiago'))->first();

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad([
            'province_id' => $provinciaA->id,
            'city_id' => $ciudadDeOtra->id,
        ]))
        ->assertSessionHasErrors('city_id');
});

it('rechaza un sector que no pertenece a la ciudad', function () {
    $this->seed(LocationSeeder::class);

    $ciudad = City::where('slug', 'santo-domingo')->first();
    $sectorDeOtra = Sector::whereHas('city', fn ($q) => $q->where('slug', 'punta-cana'))->first();

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad([
            'province_id' => $ciudad->province_id,
            'city_id' => $ciudad->id,
            'sector_id' => $sectorDeOtra->id,
        ]))
        ->assertSessionHasErrors('sector_id');
});

it('no deja mostrar la ubicacion exacta sin coordenadas', function () {
    // Dejaria el mapa del detalle vacio.
    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad(['show_exact_location' => '1']))
        ->assertSessionHasErrors('latitude');
});

it('acepta la ubicacion exacta con coordenadas', function () {
    $this->actingAs($this->admin)
        ->post('/admin/propiedades', datosPropiedad([
            'show_exact_location' => '1',
            'latitude' => '18.4861',
            'longitude' => '-69.9312',
        ]))
        ->assertSessionHasNoErrors();
});

it('rechaza un slug con mayusculas o espacios', function () {
    $datos = datosPropiedad();
    $datos['translations']['es']['slug'] = 'Slug Con Espacios';

    $this->actingAs($this->admin)
        ->post('/admin/propiedades', $datos)
        ->assertSessionHasErrors('translations.es.slug');
});

it('pone periodo por defecto a los alquileres', function () {
    $this->actingAs($this->admin)->post('/admin/propiedades', datosPropiedad([
        'operation_type' => OperationType::Rent->value,
        'price' => 1200,
    ]));

    expect(Property::first()->price_period)->toBe(PricePeriod::Month);
});

it('quita el periodo si la operacion es venta', function () {
    $this->actingAs($this->admin)->post('/admin/propiedades', datosPropiedad([
        'operation_type' => OperationType::Sale->value,
        'price_period' => 'month',
    ]));

    expect(Property::first()->price_period)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Editar
|--------------------------------------------------------------------------
*/

it('actualiza una propiedad existente', function () {
    $property = Property::factory()->translated()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->put("/admin/propiedades/{$property->id}", datosPropiedad([
            'price' => 555000,
        ]))
        ->assertSessionHasNoErrors();

    expect((float) $property->fresh()->price)->toBe(555000.0)
        ->and($property->fresh()->updated_by_user_id)->toBe($this->admin->id);
});

it('carga el formulario de edicion por id, no por slug', function () {
    // Un borrador puede no tener slug todavia.
    $property = Property::factory()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->get("/admin/propiedades/{$property->id}/editar")
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| Publicacion
|--------------------------------------------------------------------------
*/

it('publica una propiedad', function () {
    $property = Property::factory()->translated()->draft()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$property->id}/publicar")
        ->assertSessionHasNoErrors();

    expect($property->fresh()->status)->toBe(PropertyStatus::Available)
        ->and($property->fresh()->published_at)->not->toBeNull();
});

it('no publica una propiedad sin titulo en espanol', function () {
    // Dejaria una ficha rota en el sitio publico.
    $property = Property::factory()->draft()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$property->id}/publicar")
        ->assertSessionHasErrors('publish');

    expect($property->fresh()->status)->toBe(PropertyStatus::Draft);
});

it('pausa una propiedad publicada', function () {
    $property = Property::factory()->translated()->published()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)->post("/admin/propiedades/{$property->id}/pausar");

    expect($property->fresh()->status)->toBe(PropertyStatus::Paused);
});

it('cambia el estado a vendida', function () {
    $property = Property::factory()->translated()->published()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$property->id}/estado", ['status' => 'sold']);

    expect($property->fresh()->status)->toBe(PropertyStatus::Sold);
});

it('rechaza un estado inventado', function () {
    $property = Property::factory()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$property->id}/estado", ['status' => 'inventado'])
        ->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Papelera
|--------------------------------------------------------------------------
*/

it('envia a la papelera sin borrar de verdad', function () {
    $property = Property::factory()->translated()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($this->admin)->delete("/admin/propiedades/{$property->id}");

    expect(Property::count())->toBe(0)
        ->and(Property::withTrashed()->count())->toBe(1);
});

it('restaura una propiedad de la papelera', function () {
    $property = Property::factory()->translated()->create(['property_type_id' => $this->tipo->id]);
    $property->delete();

    $this->actingAs($this->admin)->post("/admin/propiedades/{$property->id}/restaurar");

    expect(Property::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Permisos
|--------------------------------------------------------------------------
*/

it('un editor puede crear propiedades', function () {
    $editor = userWithRole('editor');

    $this->actingAs($editor)
        ->post('/admin/propiedades', datosPropiedad())
        ->assertSessionHasNoErrors();
});

it('un editor no puede borrar propiedades', function () {
    $editor = userWithRole('editor');
    $property = Property::factory()->create(['property_type_id' => $this->tipo->id]);

    $this->actingAs($editor)
        ->delete("/admin/propiedades/{$property->id}")
        ->assertForbidden();
});

it('un agente solo edita sus propias propiedades', function () {
    $usuario = userWithRole('agent');
    $agente = Agent::create(['user_id' => $usuario->id, 'name' => 'Carlos Mendoza']);
    $otroAgente = Agent::create(['name' => 'Otra Persona']);

    $suya = Property::factory()->create([
        'property_type_id' => $this->tipo->id,
        'agent_id' => $agente->id,
    ]);

    $ajena = Property::factory()->create([
        'property_type_id' => $this->tipo->id,
        'agent_id' => $otroAgente->id,
    ]);

    $this->actingAs($usuario)->get("/admin/propiedades/{$suya->id}/editar")->assertOk();
    $this->actingAs($usuario)->get("/admin/propiedades/{$ajena->id}/editar")->assertForbidden();
});

it('un agente no puede publicar ni borrar', function () {
    $usuario = userWithRole('agent');
    $agente = Agent::create(['user_id' => $usuario->id, 'name' => 'Carlos Mendoza']);

    $property = Property::factory()->translated()->create([
        'property_type_id' => $this->tipo->id,
        'agent_id' => $agente->id,
    ]);

    $this->actingAs($usuario)->post("/admin/propiedades/{$property->id}/publicar")->assertForbidden();
    $this->actingAs($usuario)->delete("/admin/propiedades/{$property->id}")->assertForbidden();
});

it('un rol sin permiso no entra al listado', function () {
    // Un usuario sin ningun rol relevante.
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->get('/admin/propiedades')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Selects encadenados
|--------------------------------------------------------------------------
*/

it('devuelve las ciudades de una provincia', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::where('slug', 'la-altagracia')->first();

    $this->actingAs($this->admin)
        ->getJson("/admin/ubicaciones/ciudades/{$provincia->id}")
        ->assertOk()
        ->assertJsonFragment(['name' => 'Punta Cana']);
});

it('devuelve los sectores de una ciudad', function () {
    $this->seed(LocationSeeder::class);
    $ciudad = City::where('slug', 'punta-cana')->first();

    $this->actingAs($this->admin)
        ->getJson("/admin/ubicaciones/sectores/{$ciudad->id}")
        ->assertOk()
        ->assertJsonFragment(['name' => 'Cap Cana']);
});

it('no expone las ubicaciones sin sesion', function () {
    $this->seed(LocationSeeder::class);
    $provincia = Province::first();

    $this->get("/admin/ubicaciones/ciudades/{$provincia->id}")
        ->assertRedirect(route('admin.login'));
});
