<?php

use App\Modules\Agents\Models\Agent;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('super_admin');
});

/*
|--------------------------------------------------------------------------
| La causa raíz: dos instancias de Alpine
|--------------------------------------------------------------------------
| Livewire 3 trae su propia copia de Alpine y la arranca él. Si la página
| arranca además otra, Livewire no llega a registrar sus componentes y TODOS
| los botones del panel dejan de responder, en silencio y sin error.
|
| Estas pruebas vigilan la separación de paquetes que lo evita.
*/

/** Nombres de los .js que carga una pantalla. Vite les pone hash, así que se
 *  compara por prefijo y no por el nombre del fichero fuente. */
function paquetesDe(string $html): array
{
    preg_match_all('#/build/assets/([a-zA-Z-]+)-[A-Za-z0-9_-]+\.js#', $html, $m);

    return array_values(array_unique($m[1]));
}

it('el panel no carga el paquete que arranca Alpine', function () {
    $paquetes = paquetesDe($this->actingAs($this->admin)->get('/admin')->assertOk()->getContent());

    expect($paquetes)->toContain('admin')
        ->and($paquetes)->not->toContain('app');
});

it('el sitio publico si carga el paquete con Alpine', function () {
    // El público no usa Livewire, así que necesita su propio Alpine.
    $paquetes = paquetesDe($this->get('/')->assertOk()->getContent());

    expect($paquetes)->toContain('app')
        ->and($paquetes)->not->toContain('admin');
});

it('el paquete del panel no importa alpine', function () {
    // Se quitan los comentarios antes de mirar: la cabecera del fichero cita
    // literalmente `import Alpine from 'alpinejs'` para explicar por qué NO
    // se hace, y buscar la cadena a secas daba un falso positivo.
    $fuente = file_get_contents(resource_path('js/admin.js'));
    $codigo = preg_replace('#/\*.*?\*/|//.*#s', '', $fuente);

    expect($codigo)->not->toContain("from 'alpinejs'")
        ->and($codigo)->not->toContain('Alpine.start()');
});

/*
|--------------------------------------------------------------------------
| Miniaturas
|--------------------------------------------------------------------------
*/

it('el listado del panel muestra la miniatura de la imagen principal', function () {
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Con portada']]);

    $imagen = PropertyImage::factory()->create([
        'property_id' => $p->id,
        'is_main' => true,
        'thumbnail_path' => 'properties/1/portada-thumb.webp',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/propiedades')
        ->assertOk()
        ->assertSee($imagen->thumbnailUrl(), escape: false);
});

it('sin imagen principal el listado muestra un marcador, no un hueco', function () {
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Sin portada']]);

    $this->actingAs($this->admin)
        ->get('/admin/propiedades')
        ->assertOk()
        ->assertSee('no_photography', escape: false);
});

it('la pantalla de edicion muestra la imagen principal', function () {
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Editable']]);

    $imagen = PropertyImage::factory()->create(['property_id' => $p->id, 'is_main' => true]);

    $this->actingAs($this->admin)
        ->get("/admin/propiedades/{$p->id}/editar")
        ->assertOk()
        ->assertSee($imagen->thumbnailUrl(), escape: false)
        ->assertSee($imagen->original_name, escape: false);
});

it('el alta de propiedad no muestra el bloque de imagen principal', function () {
    // Todavía no existe la propiedad a la que colgar fotos; un hueco vacío
    // ahí solo confunde.
    $this->actingAs($this->admin)
        ->get('/admin/propiedades/crear')
        ->assertOk()
        ->assertDontSee('IMAGEN PRINCIPAL', escape: false);
});

it('los botones de la miniatura apuntan a una pestana que existe', function () {
    // Un @click a una pestaña inexistente deja la pantalla en blanco.
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Pestañas']]);

    $html = $this->actingAs($this->admin)->get("/admin/propiedades/{$p->id}/editar")->getContent();

    preg_match_all("/tab = '([a-z]+)'/", $html, $m);
    $destinos = array_unique($m[1]);

    // Las pestañas que el formulario declara en su barra de navegación.
    $existentes = ['general', 'price', 'location', 'features', 'amenities', 'content', 'media', 'contact', 'seo'];

    foreach ($destinos as $destino) {
        expect($existentes)->toContain($destino);
    }
});

/*
|--------------------------------------------------------------------------
| Gráficas del dashboard
|--------------------------------------------------------------------------
*/

it('el reparto por tipo sale de datos reales', function () {
    $tipo = PropertyType::first();

    foreach (range(1, 3) as $i) {
        $p = Property::factory()->published()->create(['property_type_id' => $tipo->id]);
        app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => "Casa {$i}"]]);
    }

    $reparto = app(ReportService::class)->byType();

    expect($reparto->first()['nombre'])->toBe($tipo->name)
        ->and($reparto->first()['total'])->toBe(3);
});

it('el reparto por estado omite los estados sin propiedades', function () {
    // Una gráfica con seis barras a cero no informa de nada.
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Única']]);

    $reparto = app(ReportService::class)->byStatus();

    expect($reparto)->toHaveCount(1)
        ->and($reparto->first()['total'])->toBe(1)
        ->and($reparto->first()['color'])->toStartWith('var(--color-');
});

it('los repartos solo cuentan lo publicado', function () {
    Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'published_at' => null,
    ]);

    expect(app(ReportService::class)->byType()->sum('total'))->toBe(0);
});

it('el dashboard pinta las dos graficas', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee(__('admin/reports.charts.by_type'), escape: false)
        ->assertSee(__('admin/reports.charts.by_status'), escape: false);
});

it('un editor tambien ve el reparto del catalogo', function () {
    // Es información de contenido, no de negocio.
    $this->actingAs(userWithRole('editor'))
        ->get('/admin')
        ->assertOk()
        ->assertSee(__('admin/reports.charts.by_type'), escape: false);
});

/*
|--------------------------------------------------------------------------
| Guía del módulo de contenido
|--------------------------------------------------------------------------
*/

it('contenido ofrece la guia y sus cinco pasos', function () {
    $html = $this->actingAs($this->admin)->get('/admin/contenido')->assertOk()->getContent();

    expect($html)->toContain(__('admin/content.guide.open'));

    foreach (__('admin/content.guide.steps') as $paso) {
        expect($html)->toContain($paso['titulo']);
    }
});

/*
|--------------------------------------------------------------------------
| Clics de WhatsApp
|--------------------------------------------------------------------------
*/

it('el modulo de whatsapp explica que no envia mensajes', function () {
    // Se llamaba «WhatsApp» y parecía una integración de mensajería.
    $this->actingAs($this->admin)
        ->get('/admin/whatsapp')
        ->assertOk()
        ->assertSee(__('admin/whatsapp.subtitle'), escape: false);
});

it('el menu lo nombra como clics, no como whatsapp a secas', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Clics de WhatsApp', escape: false);
});

/*
|--------------------------------------------------------------------------
| Filtro por asesor
|--------------------------------------------------------------------------
*/

it('el filtro por asesor se pinta cuando hay asesores', function () {
    // Existía en el componente y en la consulta, pero nunca se dibujó.
    Agent::create(['name' => 'Carlos Mendoza', 'is_active' => true]);

    $this->actingAs($this->admin)
        ->get('/admin/propiedades')
        ->assertOk()
        ->assertSee('wire:model.live="agent"', escape: false)
        ->assertSee('Carlos Mendoza', escape: false);
});

it('sin asesores el filtro no se pinta', function () {
    $this->actingAs($this->admin)
        ->get('/admin/propiedades')
        ->assertOk()
        ->assertDontSee('wire:model.live="agent"', escape: false);
});
