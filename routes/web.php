<?php

use App\Modules\Compare\Controllers\Public\CompareController;
use App\Modules\Pages\Controllers\Public\HomeController;
use App\Modules\Pages\Controllers\Public\PlaceholderController;
use App\Modules\Properties\Controllers\Public\PropertyController;
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

foreach (Locale::codes() as $locale) {

    Route::prefix(Locale::prefix($locale))
        ->name("{$locale}.")
        ->middleware('set.locale')
        ->group(function () use ($locale) {

            $seg = fn (string $key) => Locale::segment($key, $locale);

            Route::get('/', [HomeController::class, 'index'])->name('home');

            Route::get($seg('properties'), [PropertyController::class, 'index'])
                ->name('properties.index');

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

            Route::get($seg('invest'), [PlaceholderController::class, 'invest'])
                ->name('invest.index');

            Route::get($seg('about'), [PlaceholderController::class, 'about'])
                ->name('about.index');

            Route::get($seg('news'), [PlaceholderController::class, 'news'])
                ->name('news.index');

            Route::get($seg('news').'/{slug}', [PlaceholderController::class, 'newsDetail'])
                ->name('news.show');

            Route::get($seg('contact'), [PlaceholderController::class, 'contact'])
                ->name('contact.index');

            Route::get($seg('publish'), [PlaceholderController::class, 'publish'])
                ->name('publish.index');

            Route::get($seg('privacy'), [PlaceholderController::class, 'privacy'])
                ->name('privacy');

            Route::get($seg('terms'), [PlaceholderController::class, 'terms'])
                ->name('terms');
        });
}
