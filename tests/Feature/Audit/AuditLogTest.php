<?php

use App\Enums\AuditAction;
use App\Enums\NewsStatus;
use App\Enums\PropertyStatus;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Services\AuditService;
use App\Modules\News\Models\NewsPost;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('admin');
});

/*
|--------------------------------------------------------------------------
| Censura de credenciales
|--------------------------------------------------------------------------
| La parte delicada del modulo. El registro lo lee cualquier administrador y
| guarda el "antes" y el "despues" de cada cambio: sin censura, cambiar la
| contrasena del SMTP la dejaria escrita en claro DOS veces en una tabla
| consultable. Seria un agujero peor que el problema que la auditoria
| venia a resolver.
*/

it('nunca escribe una contrasena en el registro', function () {
    $this->actingAs($this->admin);

    app(SettingsService::class)->set('mail_password', 'ClaveSuperSecreta123');

    $apunte = AuditLog::where('action', AuditAction::MailChanged)->firstOrFail();

    $todo = json_encode([$apunte->old_values, $apunte->new_values]);

    expect($todo)->not->toContain('ClaveSuperSecreta123')
        ->and($apunte->new_values['mail_password'])->toBe(AuditService::MASK);
});

it('censura cualquier clave que suene a credencial', function (string $clave) {
    expect(app(AuditService::class)->isSensitive($clave))->toBeTrue();
})->with([
    'password', 'mail_password', 'MAIL_PASSWORD', 'api_token',
    'stripe_secret', 'remember_token', 'private_key', 'webhook_signature',
]);

it('no censura las claves normales', function (string $clave) {
    expect(app(AuditService::class)->isSensitive($clave))->toBeFalse();
})->with(['site_name', 'contact_email', 'status', 'title', 'price']);

it('censura tambien dentro de arrays anidados', function () {
    // Los ajustes llegan anidados; sin recorrer el arbol la censura se saltaria.
    $limpio = app(AuditService::class)->redact([
        'mail' => ['host' => 'smtp.test', 'password' => 'secreta'],
        'site_name' => 'ERA',
    ]);

    expect($limpio['mail']['password'])->toBe(AuditService::MASK)
        ->and($limpio['mail']['host'])->toBe('smtp.test')
        ->and($limpio['site_name'])->toBe('ERA');
});

it('el intento fallido guarda el correo pero no la contrasena', function () {
    $this->post('/admin/login', [
        'email' => 'atacante@ejemplo.test',
        'password' => 'IntentoDeClave999',
    ]);

    $apunte = AuditLog::where('action', AuditAction::LoginFailed)->firstOrFail();

    expect($apunte->entity_label)->toBe('atacante@ejemplo.test')
        ->and(json_encode($apunte->new_values))->not->toContain('IntentoDeClave999');
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('registra el inicio de sesion con ip y navegador', function () {
    $usuario = userWithRole('admin', ['email' => 'jefe@erarealtyrd.com']);

    $this->post('/admin/login', ['email' => $usuario->email, 'password' => 'password']);

    $apunte = AuditLog::where('action', AuditAction::Login)->firstOrFail();

    expect($apunte->user_id)->toBe($usuario->id)
        ->and($apunte->user_name)->toBe($usuario->name)
        ->and($apunte->ip_address)->not->toBeNull();
});

it('registra el cierre de sesion con su autor', function () {
    // Se anota ANTES del logout: despues ya no habria usuario autenticado y
    // el apunte quedaria huerfano.
    $this->actingAs($this->admin)->post('/admin/logout');

    expect(AuditLog::where('action', AuditAction::Logout)->first()?->user_id)
        ->toBe($this->admin->id);
});

/*
|--------------------------------------------------------------------------
| Propiedades
|--------------------------------------------------------------------------
*/

it('registra el alta de una propiedad', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);

    expect(AuditLog::where('action', AuditAction::PropertyCreated)->count())->toBe(1)
        ->and(AuditLog::first()->entity_id)->toBe($property->id);
});

it('distingue el cambio de estado de una edicion cualquiera', function () {
    // Pasar una villa a "vendida" es una decision de negocio, no una edicion
    // mas: merece su propia accion en el listado.
    $this->actingAs($this->admin);

    $property = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Available,
        'bedrooms' => 2,
    ]);

    $property->update(['bedrooms' => 4]);
    $property->update(['status' => PropertyStatus::Sold]);

    expect(AuditLog::where('action', AuditAction::PropertyUpdated)->count())->toBe(1)
        ->and(AuditLog::where('action', AuditAction::PropertyStatusChanged)->count())->toBe(1);
});

it('un solo guardado no genera dos apuntes', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'status' => PropertyStatus::Available,
    ]);

    AuditLog::query()->delete();

    // Cambia el estado Y otro campo a la vez: debe salir UN apunte.
    $property->update(['status' => PropertyStatus::Reserved, 'bedrooms' => 5]);

    expect(AuditLog::count())->toBe(1)
        ->and(AuditLog::first()->action)->toBe(AuditAction::PropertyStatusChanged);
});

it('guarda solo los campos que cambiaron', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'bedrooms' => 2,
    ]);

    AuditLog::query()->delete();
    $property->update(['bedrooms' => 3]);

    $apunte = AuditLog::firstOrFail();

    expect(array_keys($apunte->new_values))->toBe(['bedrooms'])
        ->and($apunte->old_values['bedrooms'])->toBe(2)
        ->and($apunte->new_values['bedrooms'])->toBe(3);
});

it('no registra un guardado que no cambia nada', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);
    AuditLog::query()->delete();

    $property->save();

    expect(AuditLog::count())->toBe(0);
});

it('registra el borrado de una propiedad', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);
    $property->delete();

    expect(AuditLog::where('action', AuditAction::PropertyDeleted)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Imagenes y noticias
|--------------------------------------------------------------------------
*/

it('registra la subida y el borrado de una imagen', function () {
    $this->actingAs($this->admin);

    $property = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);
    $imagen = PropertyImage::factory()->create(['property_id' => $property->id]);

    expect(AuditLog::where('action', AuditAction::ImageUploaded)->count())->toBe(1);

    $imagen->delete();

    expect(AuditLog::where('action', AuditAction::ImageDeleted)->count())->toBe(1);
});

it('registra la publicacion de una noticia, no cada guardado del borrador', function () {
    $this->actingAs($this->admin);

    $post = NewsPost::factory()->create(['status' => NewsStatus::Draft, 'published_at' => null]);

    $post->update(['reading_time' => 5]);
    expect(AuditLog::where('action', AuditAction::NewsPublished)->count())->toBe(0);

    $post->update(['status' => NewsStatus::Published, 'published_at' => now()]);
    expect(AuditLog::where('action', AuditAction::NewsPublished)->count())->toBe(1);
});

it('registra el borrado de una noticia', function () {
    $this->actingAs($this->admin);

    NewsPost::factory()->create()->delete();

    expect(AuditLog::where('action', AuditAction::NewsDeleted)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Ajustes
|--------------------------------------------------------------------------
*/

it('separa el cambio de whatsapp del de configuracion general', function () {
    $this->actingAs($this->admin);

    app(SettingsService::class)->setMany([
        'site_name' => 'Nombre distinto del sembrado',
        'contact_whatsapp_number' => '18095551234',
    ]);

    expect(AuditLog::where('action', AuditAction::SettingsChanged)->count())->toBe(1)
        ->and(AuditLog::where('action', AuditAction::WhatsappChanged)->count())->toBe(1);
});

it('el cambio de logotipo tiene su propia accion', function () {
    $this->actingAs($this->admin);

    app(SettingsService::class)->set('site_logo', 'settings/logo.webp');

    expect(AuditLog::where('action', AuditAction::LogoChanged)->count())->toBe(1);
});

it('guardar el formulario sin tocar nada no ensucia la auditoria', function () {
    $this->actingAs($this->admin);

    app(SettingsService::class)->set('site_name', 'Igual');
    AuditLog::query()->delete();

    app(SettingsService::class)->set('site_name', 'Igual');

    expect(AuditLog::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| El rastro sobrevive a su autor
|--------------------------------------------------------------------------
*/

it('borrar al usuario no borra su rastro', function () {
    // Un registro que se vacia borrando al autor no sirve para auditar nada.
    $usuario = userWithRole('admin', ['name' => 'Se va']);

    $this->actingAs($usuario);
    Property::factory()->create(['property_type_id' => PropertyType::first()->id]);

    $usuario->delete();

    $apunte = AuditLog::firstOrFail();

    expect(AuditLog::count())->toBe(1)
        ->and($apunte->user_id)->toBeNull()
        ->and($apunte->user_name)->toBe('Se va')
        ->and($apunte->authorName())->toBe('Se va');
});

/*
|--------------------------------------------------------------------------
| El fallo de la auditoria no puede tumbar la accion auditada
|--------------------------------------------------------------------------
*/

it('si la auditoria falla, la accion sigue adelante', function () {
    $this->actingAs($this->admin);

    // Se rompe la tabla a proposito para simular un fallo de escritura.
    // AuditService lo captura, lo anota en el log de la aplicacion y deja
    // que la peticion del usuario siga su curso.
    Schema::drop('audit_logs');

    Log::shouldReceive('error')->once();

    $property = Property::factory()->create(['property_type_id' => PropertyType::first()->id]);

    expect($property->exists)->toBeTrue()
        ->and(Property::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Diff
|--------------------------------------------------------------------------
*/

it('el diff empareja antes y despues y omite lo que no cambio', function () {
    $apunte = AuditLog::create([
        'action' => AuditAction::PropertyUpdated->value,
        'old_values' => ['bedrooms' => 2, 'price' => 100, 'title' => 'Igual'],
        'new_values' => ['bedrooms' => 3, 'price' => 200, 'title' => 'Igual'],
    ]);

    $diff = $apunte->diff();

    expect(array_keys($diff))->toBe(['bedrooms', 'price'])
        ->and($diff['bedrooms'])->toBe(['old' => 2, 'new' => 3]);
});

it('el diff muestra los campos que solo existen en un lado', function () {
    $apunte = AuditLog::create([
        'action' => AuditAction::PropertyCreated->value,
        'old_values' => null,
        'new_values' => ['title' => 'Nueva'],
    ]);

    expect($apunte->diff())->toBe(['title' => ['old' => null, 'new' => 'Nueva']]);
});
