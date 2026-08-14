<?php

use App\Models\User;
use App\Modules\Agents\Livewire\AgentManager;
use App\Modules\Agents\Models\Agent;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('admin');
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para gestionar agentes', function () {
    $this->get('/admin/agentes')->assertRedirect(route('admin.login'));
});

it('muestra la pantalla de agentes', function () {
    $this->actingAs($this->admin)->get('/admin/agentes')->assertOk();
});

it('niega el acceso a un usuario sin permiso', function () {
    $this->actingAs(User::factory()->create())->get('/admin/agentes')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Alta y edicion
|--------------------------------------------------------------------------
*/

it('crea un asesor con cargo en ambos idiomas', function () {
    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('create')
        ->set('name', 'Carlos Mendoza')
        ->set('position.es', 'Asesor Senior')
        ->set('position.en', 'Senior Broker')
        ->set('email', 'carlos@erarealtyrd.com')
        ->call('save')
        ->assertHasNoErrors();

    $agente = Agent::firstOrFail();

    expect($agente->name)->toBe('Carlos Mendoza')
        ->and($agente->getTranslation('position', 'es'))->toBe('Asesor Senior')
        ->and($agente->getTranslation('position', 'en'))->toBe('Senior Broker');
});

it('exige el nombre', function () {
    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');

    expect(Agent::count())->toBe(0);
});

it('rechaza un correo mal formado', function () {
    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('create')
        ->set('name', 'Carlos')
        ->set('email', 'no-es-un-correo')
        ->call('save')
        ->assertHasErrors('email');
});

it('carga los datos existentes al editar', function () {
    $agente = Agent::create([
        'name' => 'Laura Objío',
        'position' => ['es' => 'Especialista', 'en' => 'Specialist'],
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('edit', $agente->id)
        ->assertSet('name', 'Laura Objío')
        ->assertSet('position.es', 'Especialista')
        ->assertSet('position.en', 'Specialist');
});

/*
|--------------------------------------------------------------------------
| Foto
|--------------------------------------------------------------------------
*/

it('sube la foto y la guarda como webp', function () {
    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('create')
        ->set('name', 'Con foto')
        ->set('photo', UploadedFile::fake()->image('carlos.jpg', 800, 800))
        ->call('save')
        ->assertHasNoErrors();

    $ruta = Agent::firstOrFail()->photo;

    expect($ruta)->toEndWith('.webp')
        ->and($ruta)->toStartWith('agents/')
        ->and($ruta)->not->toContain('carlos')
        ->and(Storage::disk('public')->exists($ruta))->toBeTrue();
});

it('rechaza una foto demasiado pequena', function () {
    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('create')
        ->set('name', 'Foto pequeña')
        ->set('photo', UploadedFile::fake()->image('mini.jpg', 100, 100))
        ->call('save')
        ->assertHasErrors('photo');
});

it('borra la foto anterior al subir una nueva', function () {
    $manager = Livewire::actingAs($this->admin)->test(AgentManager::class);

    $manager->call('create')
        ->set('name', 'Carlos')
        ->set('photo', UploadedFile::fake()->image('primera.jpg', 600, 600))
        ->call('save');

    $agente = Agent::firstOrFail();
    $primera = $agente->photo;

    $manager->call('edit', $agente->id)
        ->set('photo', UploadedFile::fake()->image('segunda.jpg', 600, 600))
        ->call('save');

    $segunda = $agente->fresh()->photo;

    expect($segunda)->not->toBe($primera)
        ->and(Storage::disk('public')->exists($primera))->toBeFalse()
        ->and(Storage::disk('public')->exists($segunda))->toBeTrue();
});

it('permite quitar la foto', function () {
    $manager = Livewire::actingAs($this->admin)->test(AgentManager::class);

    $manager->call('create')
        ->set('name', 'Carlos')
        ->set('photo', UploadedFile::fake()->image('quitar.jpg', 600, 600))
        ->call('save');

    $agente = Agent::firstOrFail();
    $ruta = $agente->photo;

    $manager->call('removePhoto', $agente->id);

    expect($agente->fresh()->photo)->toBeNull()
        ->and(Storage::disk('public')->exists($ruta))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Visibilidad y orden
|--------------------------------------------------------------------------
*/

it('activa y desactiva sin borrar', function () {
    $agente = Agent::create(['name' => 'Carlos', 'is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('toggleActive', $agente->id);

    expect($agente->fresh()->is_active)->toBeFalse()
        ->and(Agent::find($agente->id))->not->toBeNull();
});

it('un asesor oculto no sale en el sitio publico pero conserva sus propiedades', function () {
    $agente = Agent::create(['name' => 'Agente Oculto', 'is_active' => false]);

    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
        'agent_id' => $agente->id,
    ]);

    $this->get('/sobre-nosotros')->assertDontSee('Agente Oculto', escape: false);

    expect($property->fresh()->agent_id)->toBe($agente->id);
});

it('reordena los asesores', function () {
    $primero = Agent::create(['name' => 'Primero', 'sort_order' => 0]);
    $segundo = Agent::create(['name' => 'Segundo', 'sort_order' => 1]);

    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('move', $segundo->id, 'up');

    expect($segundo->fresh()->sort_order)->toBeLessThan($primero->fresh()->sort_order);
});

/*
|--------------------------------------------------------------------------
| Borrado
|--------------------------------------------------------------------------
*/

it('avisa cuantas propiedades quedaran sin asesor', function () {
    $agente = Agent::create(['name' => 'Con cartera']);

    Property::factory()->count(3)->create([
        'property_type_id' => PropertyType::first()->id,
        'agent_id' => $agente->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('confirmDelete', $agente->id)
        ->assertSet('confirmingProperties', 3);
});

it('al borrar un asesor sus propiedades no se borran', function () {
    // Solo se quedan sin asesor asignado: perder fichas por eliminar a una
    // persona seria catastrofico.
    $agente = Agent::create(['name' => 'Se va']);

    $property = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'agent_id' => $agente->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(AgentManager::class)
        ->call('confirmDelete', $agente->id)
        ->call('delete');

    expect(Agent::count())->toBe(0)
        ->and(Property::find($property->id))->not->toBeNull()
        ->and($property->fresh()->agent_id)->toBeNull();
});

it('borra tambien la foto del disco', function () {
    $manager = Livewire::actingAs($this->admin)->test(AgentManager::class);

    $manager->call('create')
        ->set('name', 'Con foto')
        ->set('photo', UploadedFile::fake()->image('borrar.jpg', 600, 600))
        ->call('save');

    $agente = Agent::firstOrFail();
    $ruta = $agente->photo;

    $manager->call('confirmDelete', $agente->id)->call('delete');

    expect(Storage::disk('public')->exists($ruta))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Integracion con el sitio publico
|--------------------------------------------------------------------------
*/

it('el agente aparece en la ficha de propiedad', function () {
    $agente = Agent::create([
        'name' => 'Carlos Mendoza',
        'position' => ['es' => 'Asesor Senior'],
        'whatsapp' => '(829) 777-8888',
        'is_active' => true,
    ]);

    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
        'agent_id' => $agente->id,
    ]);

    app(PropertyService::class)
        ->syncTranslations($property, ['es' => ['title' => 'Con asesor']]);

    $this->get('/propiedades/con-asesor')
        ->assertOk()
        ->assertSee('Carlos Mendoza', escape: false)
        ->assertSee('Asesor Senior', escape: false)
        // El WhatsApp del detalle usa el número del agente, no el general.
        ->assertSee('wa.me/18297778888', escape: false);
});
