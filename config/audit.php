<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retencion del registro de auditoria
    |--------------------------------------------------------------------------
    |
    | Dias que se conservan los apuntes antes de que el comando programado
    | 'audit:prune' los borre.
    |
    | 365 por defecto. Es el equilibrio entre poder investigar un incidente
    | que se descubre tarde —lo habitual— y no dejar crecer la tabla sin
    | limite: con un panel activo son unos pocos miles de filas al ano.
    |
    | Los intentos de acceso fallidos se conservan MENOS tiempo: son los que
    | mas volumen generan cuando alguien lanza un ataque de fuerza bruta, y
    | su valor caduca rapido. Noventa dias sobran para detectar un patron.
    |
    */

    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    'failed_login_retention_days' => (int) env('AUDIT_FAILED_LOGIN_RETENTION_DAYS', 90),

];
