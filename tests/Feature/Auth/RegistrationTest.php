<?php

use Illuminate\Support\Facades\Route;

/*
 * El registro público está deshabilitado por defecto (FORTIFY_REGISTRATION=false):
 * un usuario auto-registrado no pertenece a ninguna empresa y quedaba asociado
 * al tenant principal, con acceso a sus datos. Las cuentas las crea el admin.
 */
test('public registration is disabled by default', function () {
    expect(Route::has('register'))->toBeFalse();
    expect(Route::has('register.store'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'accept_legal' => 'on',
    ])->assertStatus(404);

    $this->assertGuest();
});
