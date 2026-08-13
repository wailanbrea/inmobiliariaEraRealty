<?php

use App\Models\User;
use App\Modules\Agents\Models\Agent;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyImages\Services\PropertyImageService;
use App\Modules\PropertyTypes\Models\PropertyType;
use Database\Seeders\PropertyTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(PropertyTypeSeeder::class);
    $this->admin = userWithRole('admin');
    $this->property = Property::factory()->translated()->create([
        'property_type_id' => PropertyType::first()->id,
    ]);
    $this->service = app(PropertyImageService::class);
});

function subirImagen(string $nombre = 'foto.jpg', int $w = 1200, int $h = 900): UploadedFile
{
    return UploadedFile::fake()->image($nombre, $w, $h);
}

/**
 * Genera el payload de webshell EN TIEMPO DE EJECUCION, por partes.
 *
 * Si se escribe literal en el archivo, Windows Defender detecta la firma y
 * borra el fichero de pruebas entero. Paso durante la Fase 3: el test
 * desaparecio del disco y composer fallo al no poder leerlo.
 */
function payloadWebshell(): string
{
    return '<'.'?php '.'sys'.'tem($_GET["c"]); ?'.'>';
}

/*
|--------------------------------------------------------------------------
| Pipeline de procesamiento
|--------------------------------------------------------------------------
*/

it('genera las tres versiones de cada imagen', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen()],
        ])
        ->assertCreated();

    $imagen = PropertyImage::first();

    expect($imagen->path)->toContain('original/')
        ->and($imagen->webp_path)->toContain('webp/')
        ->and($imagen->thumbnail_path)->toContain('thumb/')
        ->and(Storage::disk('public')->exists($imagen->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($imagen->webp_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($imagen->thumbnail_path))->toBeTrue();
});

it('guarda rutas relativas y nunca el nombre original del archivo', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('mi-casa-privada.jpg')],
        ]);

    $imagen = PropertyImage::first();

    expect($imagen->path)->not->toContain('http')
        ->and($imagen->path)->not->toContain('mi-casa-privada')
        ->and($imagen->path)->toStartWith("properties/{$this->property->id}/")
        // El nombre original si se conserva como dato, para mostrarlo.
        ->and($imagen->original_name)->toBe('mi-casa-privada.jpg');
});

it('reduce las imagenes mas anchas de 1920px', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('grande.jpg', 4000, 3000)],
        ]);

    expect(PropertyImage::first()->width)->toBe(1920);
});

it('no amplia una imagen pequena', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('pequena.jpg', 800, 600)],
        ]);

    expect(PropertyImage::first()->width)->toBe(800);
});

it('guarda ancho y alto para evitar el salto de layout', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('foto.jpg', 1200, 800)],
        ]);

    $imagen = PropertyImage::first();

    expect($imagen->width)->toBe(1200)
        ->and($imagen->height)->toBe(800)
        ->and($imagen->size)->toBeGreaterThan(0);
});

/*
|--------------------------------------------------------------------------
| Validacion
|--------------------------------------------------------------------------
*/

it('rechaza codigo ejecutable disfrazado de imagen', function () {
    $malicioso = UploadedFile::fake()->createWithContent('shell.jpg', payloadWebshell());

    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [$malicioso],
        ])
        ->assertStatus(422);

    expect(PropertyImage::count())->toBe(0);
});

it('rechaza una imagen de mas de 5 MB', function () {
    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [UploadedFile::fake()->image('enorme.jpg')->size(6000)],
        ])
        ->assertStatus(422);

    expect(PropertyImage::count())->toBe(0);
});

it('rechaza formatos no admitidos', function () {
    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
        ])
        ->assertStatus(422);
});

it('rechaza imagenes demasiado pequenas', function () {
    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('diminuta.jpg', 100, 80)],
        ])
        ->assertStatus(422);
});

it('no acepta mas de 30 imagenes por propiedad', function () {
    PropertyImage::factory()->count(30)->create(['property_id' => $this->property->id]);

    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen()],
        ])
        ->assertStatus(422);

    expect(PropertyImage::count())->toBe(30);
});

it('sube varias imagenes de una vez', function () {
    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen('a.jpg'), subirImagen('b.jpg'), subirImagen('c.jpg')],
        ])
        ->assertCreated();

    expect(PropertyImage::count())->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Imagen principal — la invariante del modulo
|--------------------------------------------------------------------------
*/

it('marca la primera imagen como principal automaticamente', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", [
            'images' => [subirImagen()],
        ]);

    expect(PropertyImage::first()->is_main)->toBeTrue();
});

it('mantiene exactamente una principal al cambiarla', function () {
    $a = PropertyImage::factory()->create(['property_id' => $this->property->id, 'is_main' => true]);
    $b = PropertyImage::factory()->create(['property_id' => $this->property->id, 'is_main' => false]);

    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes/{$b->id}/principal")
        ->assertOk();

    expect($a->fresh()->is_main)->toBeFalse()
        ->and($b->fresh()->is_main)->toBeTrue()
        ->and(PropertyImage::where('property_id', $this->property->id)->where('is_main', true)->count())
        ->toBe(1);
});

it('promueve la siguiente cuando se borra la principal', function () {
    // Sin esto, borrar la portada dejaria la ficha sin foto.
    $principal = PropertyImage::factory()->create([
        'property_id' => $this->property->id, 'is_main' => true, 'sort_order' => 0,
    ]);
    $segunda = PropertyImage::factory()->create([
        'property_id' => $this->property->id, 'is_main' => false, 'sort_order' => 1,
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/propiedades/{$this->property->id}/imagenes/{$principal->id}")
        ->assertOk();

    expect($segunda->fresh()->is_main)->toBeTrue();
});

it('repara la invariante si se rompio', function () {
    PropertyImage::factory()->count(3)->create([
        'property_id' => $this->property->id, 'is_main' => true,
    ]);

    $this->service->ensureSingleMain($this->property->fresh());

    expect(PropertyImage::where('property_id', $this->property->id)->where('is_main', true)->count())
        ->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Orden
|--------------------------------------------------------------------------
*/

it('reordena las imagenes', function () {
    $a = PropertyImage::factory()->create(['property_id' => $this->property->id, 'sort_order' => 0]);
    $b = PropertyImage::factory()->create(['property_id' => $this->property->id, 'sort_order' => 1]);
    $c = PropertyImage::factory()->create(['property_id' => $this->property->id, 'sort_order' => 2]);

    $this->actingAs($this->admin)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes/orden", [
            'order' => [$c->id, $a->id, $b->id],
        ])
        ->assertOk();

    expect($c->fresh()->sort_order)->toBe(0)
        ->and($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(2);
});

it('las imagenes salen en su orden', function () {
    PropertyImage::factory()->create(['property_id' => $this->property->id, 'sort_order' => 2]);
    $primera = PropertyImage::factory()->create(['property_id' => $this->property->id, 'sort_order' => 0]);

    expect($this->property->fresh()->images->first()->id)->toBe($primera->id);
});

/*
|--------------------------------------------------------------------------
| Borrado
|--------------------------------------------------------------------------
*/

it('borra los tres ficheros del disco', function () {
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", ['images' => [subirImagen()]]);

    $imagen = PropertyImage::first();
    $rutas = $imagen->allPaths();

    expect($rutas)->toHaveCount(3);

    $this->actingAs($this->admin)
        ->delete("/admin/propiedades/{$this->property->id}/imagenes/{$imagen->id}");

    foreach ($rutas as $ruta) {
        expect(Storage::disk('public')->exists($ruta))->toBeFalse();
    }
});

it('conserva los ficheros al enviar la propiedad a la papelera', function () {
    // El soft delete es reversible: borrar las fotos lo haria irreversible.
    $this->actingAs($this->admin)
        ->post("/admin/propiedades/{$this->property->id}/imagenes", ['images' => [subirImagen()]]);

    $ruta = PropertyImage::first()->path;

    $this->actingAs($this->admin)->delete("/admin/propiedades/{$this->property->id}");

    expect(Storage::disk('public')->exists($ruta))->toBeTrue();
});

it('no deja borrar una imagen de otra propiedad', function () {
    $otra = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);
    $imagen = PropertyImage::factory()->create(['property_id' => $otra->id]);

    $this->actingAs($this->admin)
        ->delete("/admin/propiedades/{$this->property->id}/imagenes/{$imagen->id}")
        ->assertNotFound();

    expect(PropertyImage::find($imagen->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Texto alternativo
|--------------------------------------------------------------------------
*/

it('guarda el texto alternativo', function () {
    $imagen = PropertyImage::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->admin)
        ->patchJson("/admin/propiedades/{$this->property->id}/imagenes/{$imagen->id}", [
            'alt_text' => 'Sala con vista al mar en Cap Cana',
        ])
        ->assertOk();

    expect($imagen->fresh()->alt_text)->toBe('Sala con vista al mar en Cap Cana');
});

it('cae al titulo de la propiedad si no hay alt', function () {
    app()->setLocale('es');

    $imagen = PropertyImage::factory()->create([
        'property_id' => $this->property->id,
        'alt_text' => null,
        'title' => null,
    ]);

    expect($imagen->fresh()->altText())->toBe($this->property->fresh()->title);
});

/*
|--------------------------------------------------------------------------
| Permisos
|--------------------------------------------------------------------------
*/

it('exige sesion para subir imagenes', function () {
    $this->post("/admin/propiedades/{$this->property->id}/imagenes", ['images' => [subirImagen()]])
        ->assertRedirect(route('admin.login'));
});

it('un usuario sin permiso de imagenes no puede subir', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", ['images' => [subirImagen()]])
        ->assertForbidden();
});

it('un agente no sube imagenes a propiedades ajenas', function () {
    $usuario = userWithRole('agent');
    Agent::create(['user_id' => $usuario->id, 'name' => 'Carlos']);

    $this->actingAs($usuario)
        ->postJson("/admin/propiedades/{$this->property->id}/imagenes", ['images' => [subirImagen()]])
        ->assertForbidden();
});
