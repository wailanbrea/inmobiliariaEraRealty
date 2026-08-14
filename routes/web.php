<?php

use App\Modules\Compare\Controllers\Public\CompareController;
use App\Modules\Leads\Controllers\Public\LeadController;
use App\Modules\News\Controllers\Public\NewsController;
use App\Modules\Pages\Controllers\Public\AboutController;
use App\Modules\Pages\Controllers\Public\ContactController;
use App\Modules\Pages\Controllers\Public\HomeController;
use App\Modules\Pages\Controllers\Public\InvestController;
use App\Modules\Pages\Controllers\Public\PlaceholderController;
use App\Modules\Pages\Controllers\Public\PublishPropertyController;
use App\Modules\Properties\Controllers\Public\PropertyController;
use App\Modules\WhatsApp\Controllers\Public\WhatsappClickController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas publicas — bilingues
|--------------------------------------------------------------------------
| Cada ruta se registra una vez por idioma:
|
|   es.properties.index  ->  /propiedades
|   en.properties.index  ->  /en/properties
|
| En las vistas se usa lroute('properties.index'), que anade el prefijo del
| idioma activo. Ver docs/15_I18N.md.

*/

Route::post('wa/click', [WhatsappClickController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('whatsapp.click');

foreach (Locale::codes() as $locale) {

    Route::prefix(Locale::prefix($locale))
        ->name("{$locale}.")
        ->middleware('set.locale')
        ->group(function () use ($locale) {

            $seg = fn (string $key) => Locale::segment($key, $locale);

            Route::get('/', [HomeController::class, 'index'])->name('home');

            Route::get($seg('properties'), [PropertyController::class, 'index'])
                ->name('properties.index');

            Route::post($seg('properties').'/{slug}', [LeadController::class, 'property'])
                ->middleware('throttle:5,1')
                ->name('properties.inquiry');
            Route::get($seg('properties').'/{slug}', [PropertyController::class, 'show'])
                ->name('properties.show');

            Route::get($seg('compare'), [CompareController::class, 'index'])
                ->name('compare.index');

            // POST y no GET: cambian estado, y un prefetch del navegador
            // dispararia un GET solo.
            Route::post($seg('compare').'/{property}', [CompareController::class, 'toggle'])
                ->name('compare.toggle');
            Route::post($seg('compare').'/{property}/quitar', [CompareController::class, 'remove'])
                ->name('compare.remove');
            Route::post($seg('compare').'-vaciar', [CompareController::class, 'clear'])
                ->name('compare.clear');

            Route::get($seg('invest'), [InvestController::class, 'index'])
                ->name('invest.index');
            Route::post($seg('invest'), [LeadController::class, 'investment'])
                ->middleware('throttle:5,1')
                ->name('invest.store');

            Route::get($seg('about'), [AboutController::class, 'index'])
                ->name('about.index');

            Route::get($seg('news'), [NewsController::class, 'index'])
                ->name('news.index');

            Route::get($seg('news').'/{slug}', [NewsController::class, 'show'])
                ->name('news.show');

            Route::get($seg('contact'), [ContactController::class, 'index'])
                ->name('contact.index');
            Route::post($seg('contact'), [ContactController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('contact.store');

            Route::get($seg('publish'), [PublishPropertyController::class, 'index'])
                ->name('publish.index');
            Route::post($seg('publish'), [PublishPropertyController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('publish.store');

            Route::get($seg('privacy'), [PlaceholderController::class, 'privacy'])
                ->name('privacy');

            Route::get($seg('terms'), [PlaceholderController::class, 'terms'])
                ->name('terms');
        });
}
