<?php

use App\Enums\AuditAction;
use App\Modules\Audit\Livewire\AuditLogIndex;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('admin');
});

function apunte(array $atributos = []): AuditLog
{
    // created_at no es asignable en masa a proposito: en produccion lo pone
    // la base de datos y nadie debe poder fecharlo a mano. Para retrodatar en
    // las pruebas hay que escribirlo con una consulta directa.
    $fecha = $atributos['created_at'] ?? null;
    unset($atributos['created_at']);

    $log = AuditLog::create(array_merge([
        'action' => AuditAction::PropertyUpdated->value,
        'user_name' => 'Alguien',
        'entity_label' => 'Villa en Cap Cana',
    ], $atributos));

    if ($fecha) {
        AuditLog::withoutEvents(fn () => AuditLog::where('id', $log->id)->update(['created_at' => $fecha]));
        $log->refresh();
    }

    return $log;
}

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
| Es informacion de seguridad —quien entro, desde que IP—, no de contenido.
*/

it('exige sesion', function () {
    $this->get('/admin/auditoria')->assertRedirect(route('admin.login'));
});

it('un administrador entra', function () {
    $this->actingAs($this->admin)->get('/admin/auditoria')->assertOk();
});

it('un editor no entra', function () {
    $this->actingAs(userWithRole('editor'))->get('/admin/auditoria')->assertForbidden();
});

it('un agente no entra', function () {
    $this->actingAs(userWithRole('agent'))->get('/admin/auditoria')->assertForbidden();
});

it('el menu no ofrece auditoria a un editor', function () {
    // Mejor deshabilitada que ofreciendo un enlace que devuelve 403.
    $this->actingAs(userWithRole('editor'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee(route('admin.audit.index'), escape: false);
});

/*
|--------------------------------------------------------------------------
| Solo lectura
|--------------------------------------------------------------------------
*/

it('no expone ninguna ruta para editar ni borrar apuntes', function () {
    // Un registro de auditoria modificable desde la misma interfaz que audita
    // no vale para nada.
    $rutas = collect(Route::getRoutes())
        ->filter(fn ($r) => str_contains((string) $r->getName(), 'audit'))
        ->map(fn ($r) => implode('|', $r->methods()).' '.$r->uri());

    expect($rutas->filter(fn ($r) => str_contains($r, 'DELETE') || str_contains($r, 'PUT') || str_contains($r, 'PATCH')))
        ->toBeEmpty();
});

it('el componente no tiene metodos de escritura', function () {
    $publicos = collect((new ReflectionClass(AuditLogIndex::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->map(fn ($m) => $m->getName());

    expect($publicos)->not->toContain('delete')
        ->not->toContain('destroy')
        ->not->toContain('save')
        ->not->toContain('update');
});

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

it('filtra por accion', function () {
    apunte(['action' => AuditAction::PropertyDeleted->value, 'entity_label' => 'La borrada']);
    apunte(['action' => AuditAction::Login->value, 'entity_label' => 'El acceso']);

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->set('action', AuditAction::PropertyDeleted->value)
        ->assertSee('La borrada')
        ->assertDontSee('El acceso');
});

it('filtra por usuario', function () {
    $otro = userWithRole('admin', ['name' => 'Otro']);

    apunte(['user_id' => $this->admin->id, 'entity_label' => 'Del primero']);
    apunte(['user_id' => $otro->id, 'entity_label' => 'Del segundo']);

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->set('user', (string) $otro->id)
        ->assertSee('Del segundo')
        ->assertDontSee('Del primero');
});

it('filtra por rango de fechas', function () {
    apunte(['created_at' => now()->subDays(30), 'entity_label' => 'La vieja']);
    apunte(['created_at' => now(), 'entity_label' => 'La reciente']);

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->set('from', now()->subDays(2)->toDateString())
        ->assertSee('La reciente')
        ->assertDontSee('La vieja');
});

it('el desplegable de usuarios solo lista a quien tiene apuntes', function () {
    // Un filtro con opciones que no devuelven nada solo hace perder el tiempo.
    userWithRole('admin', ['name' => 'Sin actividad']);
    apunte(['user_id' => $this->admin->id]);

    $autores = Livewire::actingAs($this->admin)->test(AuditLogIndex::class)->instance()->authors;

    expect($autores->pluck('name')->all())->toBe([$this->admin->name]);
});

it('limpiar filtros los devuelve a cero', function () {
    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->set('action', AuditAction::Login->value)
        ->set('from', '2026-01-01')
        ->call('clearFilters')
        ->assertSet('action', '')
        ->assertSet('from', '');
});

/*
|--------------------------------------------------------------------------
| Detalle
|--------------------------------------------------------------------------
*/

it('el detalle muestra el antes y el despues', function () {
    $log = apunte([
        'old_values' => ['bedrooms' => 2],
        'new_values' => ['bedrooms' => 5],
    ]);

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->call('view', $log->id)
        ->assertSee('bedrooms')
        ->assertSee('2')
        ->assertSee('5');
});

it('el detalle muestra que la contrasena cambio, sin revelarla', function () {
    // Los dos lados llevan el mismo marcador, asi que una comparacion normal
    // los daria por iguales y diria "sin cambios" justo en el apunte que
    // registra que alguien toco el SMTP.
    $log = apunte([
        'action' => AuditAction::MailChanged->value,
        'old_values' => ['mail_password' => AuditService::MASK],
        'new_values' => ['mail_password' => AuditService::MASK],
    ]);

    expect($log->diff())->toHaveKey('mail_password');

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->call('view', $log->id)
        ->assertSee('mail_password')
        ->assertDontSee(__('admin/audit.detail.no_changes'));
});

it('cerrar el detalle lo quita', function () {
    $log = apunte();

    Livewire::actingAs($this->admin)
        ->test(AuditLogIndex::class)
        ->call('view', $log->id)
        ->assertSet('viewing', $log->id)
        ->call('closeDetail')
        ->assertSet('viewing', null);
});

/*
|--------------------------------------------------------------------------
| Poda
|--------------------------------------------------------------------------
*/

it('la poda simula por defecto y no borra nada', function () {
    // Un comando que borra el registro de auditoria en cuanto se teclea es
    // exactamente lo que no debe existir.
    apunte(['created_at' => now()->subDays(400)]);

    $this->artisan('audit:prune')
        ->expectsOutputToContain('Simulación. Nada se ha borrado.')
        ->assertSuccessful();

    expect(AuditLog::count())->toBe(1);
});

it('la poda borra solo con --force', function () {
    apunte(['created_at' => now()->subDays(400)]);
    apunte(['created_at' => now()]);

    $this->artisan('audit:prune --force')->assertSuccessful();

    expect(AuditLog::count())->toBe(1);
});

it('los accesos fallidos caducan antes que el resto', function () {
    // Son los que mas volumen generan y su valor se pierde rapido.
    apunte(['action' => AuditAction::LoginFailed->value, 'created_at' => now()->subDays(120)]);
    apunte(['action' => AuditAction::Login->value, 'created_at' => now()->subDays(120)]);

    $this->artisan('audit:prune --force')->assertSuccessful();

    expect(AuditLog::where('action', AuditAction::LoginFailed)->count())->toBe(0)
        ->and(AuditLog::where('action', AuditAction::Login)->count())->toBe(1);
});

it('no dice haber podado cuando no hay nada caducado', function () {
    apunte(['created_at' => now()]);

    $this->artisan('audit:prune --force')
        ->expectsOutputToContain('Nada que podar.')
        ->assertSuccessful();

    expect(AuditLog::count())->toBe(1);
});
