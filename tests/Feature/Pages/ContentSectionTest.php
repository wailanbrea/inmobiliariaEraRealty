<?php

use App\Models\User;
use App\Modules\Pages\Livewire\ContentSectionManager;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('admin');
    $this->hero = ContentSection::where('section_key', 'hero')->first();
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion para editar el contenido', function () {
    $this->get('/admin/contenido')->assertRedirect(route('admin.login'));
});

it('muestra la pantalla de contenido', function () {
    $this->actingAs($this->admin)
        ->get('/admin/contenido')
        ->assertOk()
        ->assertSee('Portada principal', escape: false);
});

it('niega el acceso a un usuario sin permiso', function () {
    $this->actingAs(User::factory()->create())->get('/admin/contenido')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Edicion de textos
|--------------------------------------------------------------------------
*/

it('guarda el titular en ambos idiomas', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class, ['pageKey' => 'home'])
        ->call('edit', $this->hero->id)
        ->set('fields.es.title', 'Tu hogar en el Caribe')
        ->set('fields.en.title', 'Your home in the Caribbean')
        ->call('save')
        ->assertHasNoErrors();

    $hero = $this->hero->fresh(['translations']);

    app()->setLocale('es');
    expect($hero->translated()->title)->toBe('Tu hogar en el Caribe');

    app()->setLocale('en');
    expect($hero->fresh(['translations'])->translated()->title)->toBe('Your home in the Caribbean');
});

it('el cambio se ve en el sitio publico al instante', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('fields.es.title', 'Titular nuevo del cliente')
        ->call('save');

    // Sin esto, la cache dejaria el titular viejo hasta reiniciar.
    $this->get('/')->assertSee('Titular nuevo del cliente', escape: false);
});

it('carga los textos existentes al abrir el formulario', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->assertSet('fields.es.title', 'Encuentra tu próxima propiedad en República Dominicana');
});

/*
|--------------------------------------------------------------------------
| Imagen de portada
|--------------------------------------------------------------------------
*/

it('sube la imagen de portada y la convierte a webp', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('villa.jpg', 1920, 1080))
        ->call('save')
        ->assertHasNoErrors();

    $ruta = $this->hero->fresh()->image;

    expect($ruta)->toEndWith('.webp')
        ->and($ruta)->toStartWith('content_sections/')
        ->and($ruta)->not->toContain('villa')      // nunca el nombre original
        ->and(Storage::disk('public')->exists($ruta))->toBeTrue();
});

it('reduce una imagen enorme de portada', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('enorme.jpg', 4000, 2250))
        ->call('save');

    $ruta = $this->hero->fresh()->image;
    $tamano = getimagesizefromstring(Storage::disk('public')->get($ruta));

    expect($tamano[0])->toBe(1920);
});

it('rechaza una imagen de portada demasiado pequena', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('minuscula.jpg', 300, 200))
        ->call('save')
        ->assertHasErrors('image');

    expect($this->hero->fresh()->image)->toBeNull();
});

it('rechaza un archivo que no es imagen', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->create('documento.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('image');
});

it('borra la imagen anterior al subir una nueva', function () {
    $manager = Livewire::actingAs($this->admin)->test(ContentSectionManager::class);

    $manager->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('primera.jpg', 1920, 1080))
        ->call('save');

    $primera = $this->hero->fresh()->image;

    $manager->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('segunda.jpg', 1920, 1080))
        ->call('save');

    $segunda = $this->hero->fresh()->image;

    expect($segunda)->not->toBe($primera)
        ->and(Storage::disk('public')->exists($primera))->toBeFalse()
        ->and(Storage::disk('public')->exists($segunda))->toBeTrue();
});

it('permite quitar la imagen de portada', function () {
    $manager = Livewire::actingAs($this->admin)->test(ContentSectionManager::class);

    $manager->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('quitar.jpg', 1920, 1080))
        ->call('save');

    $ruta = $this->hero->fresh()->image;

    $manager->call('removeImage', $this->hero->id);

    expect($this->hero->fresh()->image)->toBeNull()
        ->and(Storage::disk('public')->exists($ruta))->toBeFalse();
});

it('la portada aparece en el sitio cuando hay imagen', function () {
    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('edit', $this->hero->id)
        ->set('image', UploadedFile::fake()->image('portada.jpg', 1920, 1080))
        ->call('save');

    $this->get('/')->assertSee('background-image', escape: false);
});

/*
|--------------------------------------------------------------------------
| Visibilidad
|--------------------------------------------------------------------------
*/

it('permite ocultar una seccion del inicio', function () {
    $cta = ContentSection::where('section_key', 'final_cta')->first();

    Livewire::actingAs($this->admin)
        ->test(ContentSectionManager::class)
        ->call('toggleActive', $cta->id);

    expect($cta->fresh()->is_active)->toBeFalse();

    // Y deja de salir en el sitio.
    $this->get('/')->assertDontSee('¿Hablamos de tu próxima propiedad?', escape: false);
});

/*
|--------------------------------------------------------------------------
| Rutas de imagen
|--------------------------------------------------------------------------
*/

it('las URL de imagen son relativas al host, no absolutas', function () {
    // Con URL absolutas desde APP_URL, las imagenes salen rotas en cualquier
    // entorno cuyo host no coincida: 127.0.0.1, localhost, otro equipo…
    expect(Storage::disk('public')->url('content_sections/x.webp'))
        ->toStartWith('/storage/')
        ->and(config('filesystems.disks.public.url'))->toBe('/storage');
});
