<?php

use App\Modules\Agents\Controllers\Admin\AgentController;
use App\Modules\Auth\Controllers\ForgotPasswordController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\ResetPasswordController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Leads\Controllers\Admin\LeadController;
use App\Modules\Locations\Controllers\Admin\LocationLookupController;
use App\Modules\Media\Controllers\Admin\MediaController;
use App\Modules\News\Controllers\Admin\NewsCategoryController;
use App\Modules\News\Controllers\Admin\NewsPostController;
use App\Modules\Pages\Controllers\Admin\ContentController;
use App\Modules\Properties\Controllers\Admin\PropertyController;
use App\Modules\PropertyImages\Controllers\Admin\PropertyImageController;
use App\Modules\PropertyTypes\Controllers\Admin\CatalogController;
use App\Modules\Settings\Controllers\Admin\SettingsController;
use App\Modules\WhatsApp\Controllers\Admin\WhatsappReportController;
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

    Route::get('olvide-contrasena', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('olvide-contrasena', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('restablecer-contrasena/{token}', [ResetPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('restablecer-contrasena', [ResetPasswordController::class, 'store'])
        ->name('password.update');
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

    // --- Noticias ---
    Route::prefix('noticias')->name('news.')->middleware('permission:manage_news')->group(function () {
        Route::get('/', [NewsPostController::class, 'index'])->name('posts.index');
        Route::get('crear', [NewsPostController::class, 'create'])->name('posts.create');
        Route::post('/', [NewsPostController::class, 'store'])->name('posts.store');
        Route::get('{post}/editar', [NewsPostController::class, 'edit'])->name('posts.edit');
        Route::put('{post}', [NewsPostController::class, 'update'])->name('posts.update');
        Route::delete('{post}', [NewsPostController::class, 'destroy'])->name('posts.destroy');

        Route::get('categorias/listado', [NewsCategoryController::class, 'index'])->name('categories.index');
        Route::post('categorias', [NewsCategoryController::class, 'store'])->name('categories.store');
        Route::put('categorias/{category}', [NewsCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categorias/{category}', [NewsCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // --- Agentes ---
    Route::get('agentes', [AgentController::class, 'index'])->name('agents.index');

    // --- Leads ---
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('exportar', [LeadController::class, 'export'])->name('export');
        Route::get('{lead}', [LeadController::class, 'show'])->name('show');
        Route::put('{lead}', [LeadController::class, 'update'])->name('update');
    });

    // --- Contenido editable de las paginas ---
    Route::get('contenido', [ContentController::class, 'index'])->name('content.index');

    // --- Analitica de WhatsApp ---
    Route::get('whatsapp', [WhatsappReportController::class, 'index'])->name('whatsapp.index');

    // --- Biblioteca de medios ---
    Route::get('media', [MediaController::class, 'index'])->name('media.index');

    // --- Catalogos ---
    Route::prefix('catalogo')->name('catalog.')->group(function () {
        Route::get('tipos', [CatalogController::class, 'propertyTypes'])->name('types');
        Route::get('amenidades', [CatalogController::class, 'amenities'])->name('amenities');
        Route::get('ubicaciones', [CatalogController::class, 'locations'])->name('locations');
    });

    // Fase 3: imagenes, media. Fase 5: leads
    // Fase 6: noticias
    // Fase 7: agentes
    // Fase 9: reportes, auditoria
});
