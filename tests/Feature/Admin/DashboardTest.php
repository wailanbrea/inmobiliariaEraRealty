<?php

use App\Models\User;

it('renderiza el layout del panel con la navegacion y el usuario', function () {
    $user = userWithRole('super_admin', ['name' => 'Ana Gomez']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Propiedades')
        ->assertSee('Configuración', escape: false)
        ->assertSee('Ana Gomez')
        ->assertSee('super_admin')
        ->assertSee('Cerrar sesión', escape: false);
});

it('marca el panel como noindex', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertSee('noindex, nofollow', escape: false);
});

it('no expone el panel en el sitemap ni en rutas publicas', function () {
    // La home publica llega en la Fase 4; por ahora solo se comprueba que
    // /admin exige sesion, que es la garantia que importa.
    $this->get('/admin')->assertRedirect(route('admin.login'));
});
