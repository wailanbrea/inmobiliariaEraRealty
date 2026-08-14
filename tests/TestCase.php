<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Idioma que anuncian las peticiones de prueba por defecto.
     *
     * Symfony inyecta 'en-us,en;q=0.5' en toda peticion de prueba que no
     * declare la cabecera. Es un valor arbitrario del framework, no el de un
     * visitante realista de una inmobiliaria dominicana — y desde que existe
     * DetectBrowserLocale hace que cada `$this->get('/')` de la suite reciba
     * un 302 hacia /en y falle por un motivo que no tiene nada que ver con lo
     * que la prueba comprueba. Fueron 31 pruebas de golpe.
     *
     * Se fija el idioma por defecto del sitio, de modo que una prueba que no
     * habla de idiomas se comporte como el visitante mayoritario.
     * BrowserLocaleTest lo sobrescribe con withHeaders() para ejercitar el
     * camino en ingles.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'es-DO,es;q=0.9');
    }
}
