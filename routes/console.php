<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| Requieren el cron del servidor. Ver docs/09_DEPLOYMENT.md:
|
|   * * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1
*/

// Poda del registro de auditoria. Va con --force porque en el cron no hay
// nadie para confirmar; el periodo de retencion lo fija config/audit.php.
Schedule::command('audit:prune --force')
    ->weeklyOn(1, '03:15')
    ->withoutOverlapping();

// La biblioteca de medios se revisa SIN --force a proposito: solo informa.
// Un huerfano puede ser un archivo legitimo subido por otra via, asi que el
// borrado lo decide una persona mirando la lista.
Schedule::command('media:prune')
    ->monthlyOn(1, '03:45');
