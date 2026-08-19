<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Env;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| Token CSRF caducado en el login, y aislamiento del entorno
| Ver bootstrap/app.php: ambas cosas se configuran alli.
|--------------------------------------------------------------------------
*/

/**
 * Laravel traduce TokenMismatchException a HttpException 419 en
 * Handler::prepareException(), y solo despues consulta los callbacks de
 * render. Este test entrega la excepcion original al handler real para que
 * pase por esa traduccion: si el callback vuelve a filtrar por
 * TokenMismatchException, deja de ejecutarse y aqui llega un 419.
 */
it('devuelve al login cuando el token del formulario caduco', function () {
    $request = Request::create('http://localhost/admin/login', 'POST', [
        'email' => 'admin@erarealtyrd.com',
        'password' => 'lo-que-sea',
    ]);

    $response = app(ExceptionHandler::class)
        ->render($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('admin.login'));
});

it('mantiene el 419 en el resto del panel', function () {
    $request = Request::create('http://localhost/admin/propiedades', 'POST');

    $response = app(ExceptionHandler::class)
        ->render($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response->getStatusCode())->toBe(419);
});

it('no secuestra otros errores del login', function () {
    $request = Request::create('http://localhost/admin/login', 'POST');

    $response = app(ExceptionHandler::class)
        ->render($request, new NotFoundHttpException);

    expect($response->getStatusCode())->toBe(404);
});

/**
 * El servidor comparte un solo proceso de PHP entre varias aplicaciones
 * Laravel. Si el lector de .env escribe con putenv(), esas variables quedan
 * en el entorno del proceso y la siguiente aplicacion las hereda en lugar de
 * las suyas: APP_KEY ajeno, cookie de sesion ajena, 419 al iniciar sesion.
 */
it('no escribe el .env en el entorno del proceso', function () {
    expect(Env::get('APP_KEY'))->not->toBeEmpty()
        ->and(getenv('APP_KEY'))->toBeFalse();
});
