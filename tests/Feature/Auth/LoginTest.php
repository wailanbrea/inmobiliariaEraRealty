<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| Autenticacion del panel
| Cobertura definida en docs/08_TESTING.md seccion 2.
|--------------------------------------------------------------------------
*/

it('muestra la pantalla de login', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Iniciar sesión', escape: false);
});

it('permite entrar con credenciales validas', function () {
    $user = User::factory()->create([
        'email' => 'admin@erarealtyrd.com',
        'password' => 'contrasena-valida',
        'is_active' => true,
    ]);

    $this->post(route('admin.login'), [
        'email' => 'admin@erarealtyrd.com',
        'password' => 'contrasena-valida',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rechaza una contrasena incorrecta', function () {
    User::factory()->create([
        'email' => 'admin@erarealtyrd.com',
        'password' => 'contrasena-valida',
    ]);

    $this->post(route('admin.login'), [
        'email' => 'admin@erarealtyrd.com',
        'password' => 'contrasena-incorrecta',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('no revela si el correo existe', function () {
    User::factory()->create(['email' => 'existe@erarealtyrd.com']);

    $conCorreoReal = $this->from(route('admin.login'))->post(route('admin.login'), [
        'email' => 'existe@erarealtyrd.com',
        'password' => 'mal',
    ]);

    $conCorreoFalso = $this->from(route('admin.login'))->post(route('admin.login'), [
        'email' => 'noexiste@erarealtyrd.com',
        'password' => 'mal',
    ]);

    // Mismo mensaje en ambos casos: un atacante no puede enumerar correos.
    expect($conCorreoReal->getSession()->get('errors')->first('email'))
        ->toBe($conCorreoFalso->getSession()->get('errors')->first('email'));
});

it('impide entrar a un usuario desactivado', function () {
    User::factory()->create([
        'email' => 'inactivo@erarealtyrd.com',
        'password' => 'contrasena-valida',
        'is_active' => false,
    ]);

    $this->post(route('admin.login'), [
        'email' => 'inactivo@erarealtyrd.com',
        'password' => 'contrasena-valida',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('bloquea tras cinco intentos fallidos y avisa cuanto falta', function () {
    $user = User::factory()->create([
        'email' => 'admin@erarealtyrd.com',
        'password' => 'contrasena-valida',
    ]);

    foreach (range(1, 5) as $intento) {
        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'mal',
        ]);
    }

    // El sexto intento falla aunque la contrasena sea correcta.
    $respuesta = $this->from(route('admin.login'))->post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'contrasena-valida',
    ]);

    $respuesta->assertSessionHasErrors('email');
    $this->assertGuest();

    // El mensaje debe decir cuanto falta, no ser un 429 pelado.
    expect($respuesta->getSession()->get('errors')->first('email'))
        ->toContain('Demasiados intentos');

    RateLimiter::clear(Str::transliterate(Str::lower($user->email).'|127.0.0.1'));
});

it('limita tambien por IP contra el rociado de correos', function () {
    // El limitador de LoginRequest usa correo+IP, asi que rotar el correo lo
    // esquivaria. El throttle de la ruta es el que cubre ese hueco.
    foreach (range(1, 20) as $intento) {
        $this->post(route('admin.login'), [
            'email' => "objetivo{$intento}@erarealtyrd.com",
            'password' => 'mal',
        ]);
    }

    $this->post(route('admin.login'), [
        'email' => 'objetivo21@erarealtyrd.com',
        'password' => 'mal',
    ])->assertStatus(429);
});

it('permite cerrar sesion', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

it('redirige al login a quien no ha iniciado sesion', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('deja ver el dashboard a un usuario autenticado', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('aleja del login a quien ya inicio sesion', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});
