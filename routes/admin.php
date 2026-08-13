<?php

use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Locations\Controllers\Admin\LocationLookupController;
use App\Modules\Media\Controllers\Admin\MediaController;
use App\Modules\Pages\Controllers\Admin\ContentController;
use App\Modules\Properties\Controllers\Admin\PropertyController;
use App\Modules\PropertyImages\Controllers\Admin\PropertyImageController;
use App\Modules\PropertyTypes\Controllers\Admin\CatalogController;
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

    // --- Propiedades ---
    Route::prefix('propiedades')->name('properties.')->group(function () {
        Route::get('/', [PropertyController::class, 'index'])->name('index');
        Route::get('crear', [PropertyController::class, 'create'])->name('create');
        Route::post('/', [PropertyController::class, 'store'])->name('store');
        Route::get('{property:id}/editar', [PropertyController::class, 'edit'])->name('edit');
        Route::put('{property:id}', [PropertyController::class, 'update'])->name('update');
        Route::delete('{property:id}', [PropertyController::class, 'destroy'])->name('destroy');
        Route::post('{id}/restaurar', [PropertyController::class, 'restore'])->name('restore');

        Route::post('{property:id}/publicar', [PropertyController::class, 'publish'])->name('publish');
        Route::post('{property:id}/pausar', [PropertyController::class, 'pause'])->name('pause');
        Route::post('{property:id}/estado', [PropertyController::class, 'changeStatus'])->name('status');
        Route::get('{property:id}/vista-previa', [PropertyController::class, 'preview'])->name('preview');

        // --- Imagenes ---
        Route::prefix('{property:id}/imagenes')->name('images.')->group(function () {
            Route::post('/', [PropertyImageController::class, 'store'])->name('store');
            Route::post('orden', [PropertyImageController::class, 'reorder'])->name('reorder');
            Route::post('{image:id}/principal', [PropertyImageController::class, 'setMain'])->name('main');
            Route::patch('{image:id}', [PropertyImageController::class, 'update'])->name('update');
            Route::delete('{image:id}', [PropertyImageController::class, 'destroy'])->name('destroy');
        });
    });

    // --- Ubicaciones: alimentan los selects encadenados ---
    Route::prefix('ubicaciones')->name('locations.')->group(function () {
        Route::get('ciudades/{province:id}', [LocationLookupController::class, 'cities'])->name('cities');
        Route::get('sectores/{city:id}', [LocationLookupController::class, 'sectors'])->name('sectors');
    });

    // --- Contenido del inicio ---
    Route::get('contenido', [ContentController::class, 'index'])->name('content.index');

    // --- Biblioteca de medios ---
    Route::get('media', [MediaController::class, 'index'])->name('media.index');

    // --- Catalogos ---
    Route::prefix('catalogo')->name('catalog.')->group(function () {
        Route::get('tipos', [CatalogController::class, 'propertyTypes'])->name('types');
        Route::get('amenidades', [CatalogController::class, 'amenities'])->name('amenities');
        Route::get('ubicaciones', [CatalogController::class, 'locations'])->name('locations');
    });

    // Fase 3: imagenes, media
    // Fase 5: leads
    // Fase 6: noticias
    // Fase 7: agentes
    // Fase 9: reportes, auditoria
});
