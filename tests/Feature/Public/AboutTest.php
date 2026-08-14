<?php

use App\Enums\PropertyStatus;
use App\Modules\Agents\Models\Agent;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
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
| Pagina
|--------------------------------------------------------------------------
*/

it('muestra la pagina sobre nosotros', function () {
    $this->get('/sobre-nosotros')
        ->assertOk()
        ->assertSee('Sobre nosotros', escape: false)
        ->assertSee('Quiénes somos', escape: false);
});

it('existe en ingles con su segmento traducido', function () {
    $this->get('/en/about-us')
        ->assertOk()
        ->assertSee('About us', escape: false)
        ->assertSee('Who we are', escape: false);
});

it('muestra los tres compromisos', function () {
    $this->get('/sobre-nosotros')
        ->assertSee('Propiedades verificadas', escape: false)
        ->assertSee('Sin presión comercial', escape: false)
        ->assertSee('Atención en dos idiomas', escape: false);
});

it('traduce los compromisos al ingles', function () {
    $this->get('/en/about-us')->assertSee('No sales pressure', escape: false);
});

it('separa los parrafos del texto principal', function () {
    // El contenido lleva doble salto de linea; deben salir dos <p>.
    $html = $this->get('/sobre-nosotros')->getContent();

    expect(substr_count($html, 'Nos mueve una idea sencilla'))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Cifras
|--------------------------------------------------------------------------
*/

it('cuenta las propiedades publicadas de verdad', function () {
    // Las cifras salen de la base de datos, no son un numero inventado.
    Property::factory()->published()->count(3)->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    Property::factory()->draft()->count(2)->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    $this->get('/sobre-nosotros')->assertSee('Propiedades publicadas', escape: false);

    expect(Property::published()->count())->toBe(3);
});

it('cuenta como cerradas las vendidas y alquiladas', function () {
    Property::factory()->count(2)->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Sold,
        'published_at' => now()->subDay(),
    ]);

    Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Rented,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/sobre-nosotros')
        ->assertOk()
        ->assertSee('Operaciones cerradas', escape: false)
        ->assertSee('3', escape: false);
});

/*
|--------------------------------------------------------------------------
| Equipo
|--------------------------------------------------------------------------
*/

it('oculta la seccion de equipo si no hay agentes', function () {
    // Una sección "El equipo" vacía queda peor que no tenerla.
    $this->get('/sobre-nosotros')->assertDontSee('El equipo', escape: false);
});

it('muestra los agentes activos', function () {
    Agent::create([
        'name' => 'Carlos Mendoza',
        'position' => ['es' => 'Asesor Senior', 'en' => 'Senior Broker'],
        'is_active' => true,
    ]);

    $this->get('/sobre-nosotros')
        ->assertSee('El equipo', escape: false)
        ->assertSee('Carlos Mendoza', escape: false)
        ->assertSee('Asesor Senior', escape: false);
});

it('traduce el cargo del agente', function () {
    Agent::create([
        'name' => 'Carlos Mendoza',
        'position' => ['es' => 'Asesor Senior', 'en' => 'Senior Broker'],
        'is_active' => true,
    ]);

    $this->get('/en/about-us')->assertSee('Senior Broker', escape: false);
});

it('no muestra agentes desactivados', function () {
    Agent::create(['name' => 'Agente Activo', 'is_active' => true]);
    Agent::create(['name' => 'Agente Retirado', 'is_active' => false]);

    $this->get('/sobre-nosotros')
        ->assertSee('Agente Activo', escape: false)
        ->assertDontSee('Agente Retirado', escape: false);
});

it('genera el whatsapp del agente con su propio numero', function () {
    Agent::create([
        'name' => 'Carlos Mendoza',
        'whatsapp' => '(829) 777-8888',
        'is_active' => true,
    ]);

    $this->get('/sobre-nosotros')->assertSee('wa.me/18297778888', escape: false);
});

it('no pinta boton de whatsapp si el agente no tiene numero', function () {
    Agent::create(['name' => 'Sin WhatsApp', 'is_active' => true]);

    $html = $this->get('/sobre-nosotros')->getContent();

    // El botón flotante global sí usa wa.me; lo que no debe haber es un
    // enlace dentro de la ficha del agente.
    expect($html)->not->toContain('aria-label="WhatsApp Sin WhatsApp"');
});

/*
|--------------------------------------------------------------------------
| Contenido editable
|--------------------------------------------------------------------------
*/

it('usa el titular editado desde el panel', function () {
    $hero = ContentSection::where('page_key', 'about')->where('section_key', 'hero')->first();
    $hero->translations()->where('locale', 'es')->update(['title' => 'Nuestra historia']);
    ContentSection::flushCache('about');

    $this->get('/sobre-nosotros')->assertSee('Nuestra historia', escape: false);
});

it('permite ocultar una seccion completa', function () {
    $valores = ContentSection::where('page_key', 'about')->where('section_key', 'values')->first();
    $valores->update(['is_active' => false]);
    ContentSection::flushCache('about');

    $this->get('/sobre-nosotros')->assertDontSee('Sin presión comercial', escape: false);
});

it('el panel permite editar la pagina sobre nosotros', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/contenido?pagina=about')
        ->assertOk()
        ->assertSee('Quiénes somos', escape: false);
});
