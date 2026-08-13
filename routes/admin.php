<?php

use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Settings\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del panel administrativo
|--------------------------------------------------------------------------
| Cargadas en bootstrap/app.php con prefijo /admin y nombre admin.*
| Mapa completo en docs/01_ARCHITECTURE.md seccion 7.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    // Dos limitadores con funciones distintas:
    //  - Aqui, un tope amplio por IP: frena a quien rocia muchos correos
    //    distintos desde una misma direccion.
    //  - En LoginRequest, 5 intentos por correo+IP, que es el que devuelve
    //    el mensaje util ("vuelve a intentarlo en N segundos").
    // Si este fuera mas estricto, taparia al otro y el usuario legitimo veria
    // un 429 sin explicacion.
    Route::post('login', [LoginController::class, 'store'])
        ->name('login.store')
        ->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Configuracion ---
    Route::prefix('configuracion')->name('settings.')->group(function () {
        Route::get('general', [SettingsController::class, 'general'])->name('general');
        Route::put('general', [SettingsController::class, 'updateGeneral'])->name('general.update');

        Route::get('whatsapp', [SettingsController::class, 'whatsapp'])->name('whatsapp');
        Route::put('whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('whatsapp.update');

        Route::get('correo', [SettingsController::class, 'mail'])->name('mail');
        Route::put('correo', [SettingsController::class, 'updateMail'])
            ->name('mail.update')
            ->middleware('throttle:10,1');   // el envio de prueba toca red

        Route::get('seo', [SettingsController::class, 'seo'])->name('seo');
        Route::put('seo', [SettingsController::class, 'updateSeo'])->name('seo.update');

        Route::delete('imagen/{key}', [SettingsController::class, 'removeImage'])->name('image.remove');
    });

    // Fase 2: propiedades, tipos, ubicaciones
    // Fase 3: imagenes, media
    // Fase 5: leads
    // Fase 6: noticias
    // Fase 7: agentes
    // Fase 9: reportes, auditoria
});
