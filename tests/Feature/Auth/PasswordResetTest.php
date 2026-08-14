<?php

use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
});

it('muestra el enlace y formulario de recuperacion', function () {
    $this->get('/admin/login')->assertOk()->assertSee(route('admin.password.request'), false);
    $this->get(route('admin.password.request'))->assertOk()->assertSee('Recuperar', false);
});

it('envia el enlace solo a una cuenta activa sin revelar existencia', function () {
    $active = User::factory()->create(['email' => 'active@example.com', 'is_active' => true]);
    $inactive = User::factory()->create(['email' => 'inactive@example.com', 'is_active' => false]);

    $this->post(route('admin.password.email'), ['email' => $active->email])->assertSessionHas('status');
    $this->post(route('admin.password.email'), ['email' => $inactive->email])->assertSessionHas('status');
    $this->post(route('admin.password.email'), ['email' => 'missing@example.com'])->assertSessionHas('status');

    Notification::assertSentTo($active, AdminResetPasswordNotification::class);
    Notification::assertNotSentTo($inactive, AdminResetPasswordNotification::class);
});

it('restablece la contrasena con un token valido', function () {
    $user = User::factory()->create(['email' => 'admin@example.com', 'is_active' => true, 'must_change_password' => true]);
    $token = Password::broker()->createToken($user);

    $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClave2026',
        'password_confirmation' => 'NuevaClave2026',
    ])->assertRedirect(route('admin.login'));

    $user->refresh();
    expect(Hash::check('NuevaClave2026', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();
});

it('impide restablecer el acceso de una cuenta inactiva', function () {
    $user = User::factory()->create(['email' => 'inactive@example.com', 'is_active' => false]);
    $token = Password::broker()->createToken($user);

    $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClave2026',
        'password_confirmation' => 'NuevaClave2026',
    ])->assertSessionHasErrors('email');
});
