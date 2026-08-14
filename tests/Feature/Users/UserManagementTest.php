<?php

use App\Models\User;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Users\Livewire\UserManager;
use App\Modules\Users\Services\UserGuard;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    app(SettingsService::class)->flush();

    $this->jefe = userWithRole('super_admin', ['name' => 'Ana Gómez']);
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
| Repartir accesos es lo único reservado al super_admin: un 'admin' toca todo
| el contenido pero no puede crear cuentas. Esa separación es la que impide
| que un panel comprometido se convierta en un panel perdido.
*/

it('exige sesion', function () {
    $this->get('/admin/usuarios')->assertRedirect(route('admin.login'));
});

it('un super_admin entra', function () {
    $this->actingAs($this->jefe)->get('/admin/usuarios')->assertOk();
});

it('ni siquiera un admin entra', function (string $rol) {
    $this->actingAs(userWithRole($rol))->get('/admin/usuarios')->assertForbidden();
})->with(['admin', 'editor', 'agent']);

it('el menu solo ofrece usuarios al super_admin', function () {
    $this->actingAs(userWithRole('admin'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee(route('admin.users.index'), escape: false);
});

/*
|--------------------------------------------------------------------------
| Alta sin correo
|--------------------------------------------------------------------------
| El motivo por el que esta pantalla existe: dar de alta a alguien sin que
| salga un solo mensaje del servidor.
*/

it('crea un usuario y muestra su contrasena una sola vez', function () {
    $componente = Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'Carlos Mendoza')
        ->set('email', 'carlos@erarealtyrd.com')
        ->set('role', 'editor')
        ->call('save')
        ->assertHasNoErrors();

    $clave = $componente->get('generatedPassword');
    $nuevo = User::where('email', 'carlos@erarealtyrd.com')->firstOrFail();

    expect($clave)->not->toBeNull()
        ->and($nuevo->hasRole('editor'))->toBeTrue()
        ->and($nuevo->is_active)->toBeTrue()
        // La clave generada sirve para entrar UNA vez.
        ->and($nuevo->must_change_password)->toBeTrue()
        ->and(Hash::check($clave, $nuevo->password))->toBeTrue();

    // Al descartarla desaparece del componente: no queda en pantalla.
    $componente->call('dismissPassword')->assertSet('generatedPassword', null);
});

it('la contrasena generada no lleva caracteres que se confunden al dictarla', function () {
    $clave = Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('create')->set('name', 'X')->set('email', 'x@erarealtyrd.com')->call('save')
        ->get('generatedPassword');

    // Sin 0/O ni 1/l/I: esta clave se pasa por teléfono o WhatsApp.
    expect($clave)->not->toMatch('/[01OIl]/')
        ->and(strlen($clave))->toBe(19);   // 4 grupos de 4 + 3 guiones
});

it('rechaza un correo repetido', function () {
    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'Otro')
        ->set('email', $this->jefe->email)
        ->call('save')
        ->assertHasErrors('email');
});

/*
|--------------------------------------------------------------------------
| Restablecer contraseña
|--------------------------------------------------------------------------
*/

it('restablece la contrasena de otro usuario sin enviar correo', function () {
    $otro = userWithRole('editor');
    $anterior = $otro->password;

    $componente = Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('resetPassword', $otro->id);

    $otro->refresh();

    expect($componente->get('generatedPassword'))->not->toBeNull()
        ->and($otro->password)->not->toBe($anterior)
        ->and($otro->must_change_password)->toBeTrue()
        ->and(Hash::check($componente->get('generatedPassword'), $otro->password))->toBeTrue();
});

it('restablecer invalida las sesiones recordadas', function () {
    // Si la clave anterior circuló, ninguna sesión abierta con ella sigue viva.
    $otro = userWithRole('editor', ['remember_token' => 'token-viejo']);

    Livewire::actingAs($this->jefe)->test(UserManager::class)->call('resetPassword', $otro->id);

    expect($otro->fresh()->remember_token)->not->toBe('token-viejo');
});

/*
|--------------------------------------------------------------------------
| Las reglas que impiden quedarse sin acceso
|--------------------------------------------------------------------------
| El escenario real: alguien se quita el rol «para ver cómo lo ve un editor»,
| o desactiva la cuenta del compañero que se fue, y resulta que era el último
| con acceso total. Sin correo configurado, eso cierra el panel para siempre.
*/

it('el ultimo super_admin activo no se puede desactivar', function () {
    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('toggleActive', $this->jefe->id)
        ->assertSet('errorMessage', __('admin/users.errors.self_deactivate'));

    expect($this->jefe->fresh()->is_active)->toBeTrue();
});

it('un super_admin no puede quitarse a si mismo el rol', function () {
    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('edit', $this->jefe->id)
        ->set('role', 'editor')
        ->call('save')
        ->assertHasErrors('role');

    expect($this->jefe->fresh()->hasRole('super_admin'))->toBeTrue();
});

it('con dos super_admin, degradar a uno es legitimo', function () {
    $segundo = userWithRole('super_admin', ['name' => 'Segundo']);

    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('edit', $segundo->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect($segundo->fresh()->hasRole('admin'))->toBeTrue();
});

it('la invariante bloquea degradar al ultimo, venga de donde venga', function () {
    // Se comprueba en el guard y no por la pantalla: llegar ahi desde la
    // interfaz es imposible por construccion —para degradar a alguien hay que
    // ser super_admin, y si existes es que el otro no es el ultimo—, pero la
    // regla tiene que sostenerse igual si algun dia se llama desde un comando
    // o desde una importacion.
    $guard = app(UserGuard::class);

    expect($guard->cannotChangeRole($this->jefe, 'editor'))
        ->toBe(__('admin/users.errors.last_super_admin'))
        ->and($guard->cannotChangeRole($this->jefe, 'super_admin'))
        ->toBeNull();
});

it('con dos super_admin si se puede desactivar a uno', function () {
    $segundo = userWithRole('super_admin');

    Livewire::actingAs($this->jefe)->test(UserManager::class)->call('toggleActive', $segundo->id);

    expect($segundo->fresh()->is_active)->toBeFalse();
});

it('un super_admin desactivado no cuenta como acceso disponible', function () {
    // Uno desactivado no puede entrar a arreglar nada.
    $segundo = userWithRole('super_admin', ['is_active' => false]);

    expect(app(UserGuard::class)->activeSuperAdmins())->toBe(1)
        ->and(app(UserGuard::class)->isLastSuperAdmin($this->jefe))->toBeTrue();
});

it('nadie puede borrarse a si mismo', function () {
    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('confirmDelete', $this->jefe->id)
        ->assertSet('confirmingDelete', null)
        ->assertSet('errorMessage', __('admin/users.errors.self_delete'));
});

it('se puede borrar a otro usuario', function () {
    $otro = userWithRole('editor');

    Livewire::actingAs($this->jefe)
        ->test(UserManager::class)
        ->call('confirmDelete', $otro->id)
        ->assertSet('confirmingDelete', $otro->id)
        ->call('delete');

    expect(User::find($otro->id))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Cambio obligatorio de contraseña
|--------------------------------------------------------------------------
| El campo existía en la tabla desde la Fase 0 pero nadie lo leía: era una
| bandera de seguridad que no hacía absolutamente nada.
*/

it('con la bandera puesta, cualquier pantalla lleva al cambio', function (string $ruta) {
    $usuario = userWithRole('admin', ['must_change_password' => true]);

    $this->actingAs($usuario)->get($ruta)->assertRedirect(route('admin.password.change'));
})->with(['/admin', '/admin/propiedades', '/admin/configuracion/general']);

it('la propia pantalla de cambio si es accesible', function () {
    // Si no, sería un bucle de redirecciones sin salida.
    $usuario = userWithRole('admin', ['must_change_password' => true]);

    $this->actingAs($usuario)->get('/admin/cambiar-contrasena')->assertOk();
});

it('cerrar sesion sigue funcionando', function () {
    $usuario = userWithRole('admin', ['must_change_password' => true]);

    $this->actingAs($usuario)->post('/admin/logout')->assertRedirect(route('admin.login'));
});

it('cambiar la contrasena levanta la bandera y devuelve al panel', function () {
    $usuario = userWithRole('admin', [
        'password' => 'ClaveGenerada1',
        'must_change_password' => true,
    ]);

    $this->actingAs($usuario)->put('/admin/cambiar-contrasena', [
        'current_password' => 'ClaveGenerada1',
        'password' => 'MiClaveNueva2026',
        'password_confirmation' => 'MiClaveNueva2026',
    ])->assertRedirect(route('admin.dashboard'));

    $usuario->refresh();

    expect($usuario->must_change_password)->toBeFalse()
        ->and(Hash::check('MiClaveNueva2026', $usuario->password))->toBeTrue();
});

it('no acepta repetir la misma contrasena', function () {
    // Dejarla puesta anula el sentido de obligar al cambio.
    $usuario = userWithRole('admin', [
        'password' => 'ClaveGenerada1',
        'must_change_password' => true,
    ]);

    $this->actingAs($usuario)->put('/admin/cambiar-contrasena', [
        'current_password' => 'ClaveGenerada1',
        'password' => 'ClaveGenerada1',
        'password_confirmation' => 'ClaveGenerada1',
    ])->assertSessionHasErrors('password');

    expect($usuario->fresh()->must_change_password)->toBeTrue();
});

it('exige la contrasena actual', function () {
    $usuario = userWithRole('admin', ['password' => 'ClaveGenerada1', 'must_change_password' => true]);

    $this->actingAs($usuario)->put('/admin/cambiar-contrasena', [
        'current_password' => 'la-que-no-es',
        'password' => 'MiClaveNueva2026',
        'password_confirmation' => 'MiClaveNueva2026',
    ])->assertSessionHasErrors('current_password');
});

it('sin la bandera, el panel funciona con normalidad', function () {
    $this->actingAs($this->jefe)->get('/admin')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Comando de consola: la última red de seguridad
|--------------------------------------------------------------------------
*/

it('restablece una contrasena desde consola', function () {
    $usuario = userWithRole('admin', ['email' => 'perdido@erarealtyrd.com', 'is_active' => false]);

    $this->artisan('admin:password perdido@erarealtyrd.com')
        ->expectsOutputToContain('Contraseña restablecida')
        ->assertSuccessful();

    $usuario->refresh();

    expect($usuario->must_change_password)->toBeTrue()
        // Se reactiva: restablecer la clave sin poder entrar no arregla nada.
        ->and($usuario->is_active)->toBeTrue();
});

it('avisa si el correo no existe', function () {
    $this->artisan('admin:password nadie@ejemplo.test')->assertFailed();
});

it('puede promover a super_admin', function () {
    // La salida real si TODOS los super_admin se pierden.
    $usuario = userWithRole('editor', ['email' => 'rescate@erarealtyrd.com']);

    $this->artisan('admin:password rescate@erarealtyrd.com --promote')->assertSuccessful();

    expect($usuario->fresh()->hasRole('super_admin'))->toBeTrue();
});

it('lista los usuarios sin tocar nada', function () {
    $antes = $this->jefe->password;

    $this->artisan('admin:password --list')->assertSuccessful();

    expect($this->jefe->fresh()->password)->toBe($antes);
});
