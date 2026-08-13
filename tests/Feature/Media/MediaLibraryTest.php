<?php

use App\Models\User;
use App\Modules\Media\Livewire\MediaManager;
use App\Modules\Media\Models\MediaFile;
use App\Modules\Media\Services\MediaLibraryService;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->admin = userWithRole('admin');
    $this->service = app(MediaLibraryService::class);
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para la biblioteca', function () {
    $this->get('/admin/media')->assertRedirect(route('admin.login'));
});

it('muestra la biblioteca al administrador', function () {
    $this->actingAs($this->admin)->get('/admin/media')->assertOk();
});

it('niega el acceso a un usuario sin permiso', function () {
    $this->actingAs(User::factory()->create())->get('/admin/media')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Subida
|--------------------------------------------------------------------------
*/

it('sube una imagen y genera sus tres versiones', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('uploads', [UploadedFile::fake()->image('banner.jpg', 1200, 800)])
        ->assertHasNoErrors();

    $media = MediaFile::first();

    expect($media)->not->toBeNull()
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($media->webp_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($media->thumbnail_path))->toBeTrue()
        ->and($media->original_name)->toBe('banner.jpg')
        ->and($media->width)->toBe(1200);
});

it('guarda el contexto elegido', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('context', 'news')
        ->set('uploads', [UploadedFile::fake()->image('noticia.jpg', 800, 600)]);

    expect(MediaFile::first()->context)->toBe('news');
});

it('reduce imagenes mayores de 1600px', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('uploads', [UploadedFile::fake()->image('grande.jpg', 3000, 2000)]);

    expect(MediaFile::first()->width)->toBe(1600);
});

it('rechaza un archivo que no es imagen', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('uploads', [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])
        ->assertHasErrors('uploads.0');

    expect(MediaFile::count())->toBe(0);
});

it('rechaza una imagen de mas de 5 MB', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('uploads', [UploadedFile::fake()->image('enorme.jpg')->size(6000)])
        ->assertHasErrors('uploads.0');
});

/*
|--------------------------------------------------------------------------
| Busqueda y filtros
|--------------------------------------------------------------------------
*/

it('busca por nombre de archivo', function () {
    MediaFile::factory()->create(['original_name' => 'villa-cap-cana.jpg']);
    MediaFile::factory()->create(['original_name' => 'apartamento-piantini.jpg']);

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('search', 'cap-cana')
        ->assertSee('villa-cap-cana.jpg')
        ->assertDontSee('apartamento-piantini.jpg');
});

it('busca por texto alternativo', function () {
    MediaFile::factory()->create([
        'original_name' => 'img1.jpg',
        'alt_text' => 'Piscina con vista al mar',
    ]);
    MediaFile::factory()->create(['original_name' => 'img2.jpg', 'alt_text' => 'Cocina moderna']);

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('search', 'piscina')
        ->assertSee('img1.jpg')
        ->assertDontSee('img2.jpg');
});

it('filtra por contexto', function () {
    MediaFile::factory()->create(['original_name' => 'de-noticia.jpg', 'context' => 'news']);
    MediaFile::factory()->create(['original_name' => 'de-agente.jpg', 'context' => 'agent']);

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('context', 'news')
        ->assertSee('de-noticia.jpg')
        ->assertDontSee('de-agente.jpg');
});

/*
|--------------------------------------------------------------------------
| Edicion
|--------------------------------------------------------------------------
*/

it('guarda el texto alternativo y el titulo', function () {
    $media = MediaFile::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->call('edit', $media->id)
        ->set('editAlt', 'Vista de la piscina infinity')
        ->set('editTitle', 'Piscina')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($media->fresh()->alt_text)->toBe('Vista de la piscina infinity')
        ->and($media->fresh()->title)->toBe('Piscina');
});

/*
|--------------------------------------------------------------------------
| Borrado con verificacion de uso
|--------------------------------------------------------------------------
*/

it('avisa de donde se usa antes de borrar', function () {
    $this->seed(SettingsSeeder::class);
    app(SettingsService::class)->flush();

    $media = MediaFile::factory()->create();
    app(SettingsService::class)->set('site_logo', $media->path);

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->call('confirmDelete', $media->id)
        ->assertSet('confirmingUsages', fn ($usos) => count($usos) === 1
            && str_contains($usos[0], 'site_logo'));
});

it('no encuentra usos de un archivo libre', function () {
    $media = MediaFile::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->call('confirmDelete', $media->id)
        ->assertSet('confirmingUsages', []);
});

it('borra el registro y sus ficheros', function () {
    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->set('uploads', [UploadedFile::fake()->image('borrar.jpg', 800, 600)]);

    $media = MediaFile::first();
    $rutas = $media->allPaths();

    Livewire::actingAs($this->admin)
        ->test(MediaManager::class)
        ->call('confirmDelete', $media->id)
        ->call('delete');

    expect(MediaFile::count())->toBe(0);

    foreach ($rutas as $ruta) {
        expect(Storage::disk('public')->exists($ruta))->toBeFalse();
    }
});

/*
|--------------------------------------------------------------------------
| Comando de limpieza
|--------------------------------------------------------------------------
*/

it('detecta ficheros huerfanos en disco', function () {
    Storage::disk('public')->put('media/2026/08/suelto.jpg', 'contenido');

    expect($this->service->findOrphans())->toContain('media/2026/08/suelto.jpg');
});

it('no marca como huerfano un fichero registrado', function () {
    $media = MediaFile::factory()->create(['path' => 'media/2026/08/registrado.jpg']);
    Storage::disk('public')->put($media->path, 'contenido');

    expect($this->service->findOrphans())->not->toContain($media->path);
});

it('detecta registros cuyo fichero desaparecio', function () {
    $media = MediaFile::factory()->create(['path' => 'media/2026/08/fantasma.jpg']);

    expect($this->service->findMissingFiles())->toContain($media->id);
});

it('el comando solo lista si no se pasa --force', function () {
    Storage::disk('public')->put('media/2026/08/suelto.jpg', 'contenido');

    $this->artisan('media:prune')
        ->expectsOutputToContain('Simulación: no se ha borrado nada.')
        ->assertSuccessful();

    // Nada se toca sin confirmacion explicita.
    expect(Storage::disk('public')->exists('media/2026/08/suelto.jpg'))->toBeTrue();
});

it('el comando informa cuando no hay nada que limpiar', function () {
    $this->artisan('media:prune')
        ->expectsOutputToContain('Nada que limpiar.')
        ->assertSuccessful();
});

it('el comando pide confirmacion incluso con --force', function () {
    Storage::disk('public')->put('media/2026/08/suelto.jpg', 'contenido');

    $this->artisan('media:prune --force')
        ->expectsConfirmation('¿Borrar definitivamente lo listado?', 'no')
        ->expectsOutputToContain('Cancelado. No se ha borrado nada.')
        ->assertSuccessful();

    expect(Storage::disk('public')->exists('media/2026/08/suelto.jpg'))->toBeTrue();
});

it('el comando borra al confirmar', function () {
    Storage::disk('public')->put('media/2026/08/suelto.jpg', 'contenido');

    $this->artisan('media:prune --force')
        ->expectsConfirmation('¿Borrar definitivamente lo listado?', 'yes')
        ->assertSuccessful();

    expect(Storage::disk('public')->exists('media/2026/08/suelto.jpg'))->toBeFalse();
});
