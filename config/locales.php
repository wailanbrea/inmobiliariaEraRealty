<?php

/*
|--------------------------------------------------------------------------
| Idiomas del sitio publico
|--------------------------------------------------------------------------
| Ver docs/15_I18N.md para el porque de cada decision.
|
| El espanol no lleva prefijo de URL y el ingles si. Es deliberado: el prompt
| maestro fija /propiedades, /invierte, etc. como requisito, y el publico
| principal es dominicano.
*/

return [

    'default' => 'es',

    'supported' => [
        'es' => [
            'name' => 'Español',
            'short' => 'ES',
            'prefix' => '',          // sin prefijo
            'html_lang' => 'es',
            'og_locale' => 'es_DO',
            'flag' => '🇩🇴',
        ],
        'en' => [
            'name' => 'English',
            'short' => 'EN',
            'prefix' => 'en',
            'html_lang' => 'en',
            'og_locale' => 'en_US',
            'flag' => '🇺🇸',
        ],
    ],

    /*
    | Segmentos de URL traducidos.
    | /propiedades  <->  /en/properties
    |
    | Se traducen (en vez de dejar /en/propiedades) porque un comprador
    | anglofono busca "properties for sale punta cana": tener la palabra en la
    | URL es justo el motivo de hacer el sitio bilingue.
    */
    'segments' => [
        'properties' => ['es' => 'propiedades', 'en' => 'properties'],
        'invest' => ['es' => 'invierte', 'en' => 'invest'],
        'about' => ['es' => 'sobre-nosotros', 'en' => 'about-us'],
        'news' => ['es' => 'informate', 'en' => 'insights'],
        'contact' => ['es' => 'contactanos', 'en' => 'contact'],
        'publish' => ['es' => 'publica-tu-propiedad', 'en' => 'list-your-property'],
        'compare' => ['es' => 'comparar', 'en' => 'compare'],
        'privacy' => ['es' => 'privacidad', 'en' => 'privacy'],
        'terms' => ['es' => 'terminos', 'en' => 'terms'],
    ],

    /*
    | Cookie donde se recuerda la eleccion del visitante.
    | No se autodetecta por IP ni por Accept-Language: Google penaliza la
    | redireccion automatica al rastrear, y un dominicano con el navegador en
    | ingles acabaria en la version que no quiere.
    */
    'cookie' => 'era_locale',
    'cookie_days' => 365,
];
