<?php

namespace App\Providers;

use App\Modules\Settings\Services\SettingsService;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola instancia por peticion: asi la coleccion de settings se
        // resuelve una vez y no se relee en cada llamada a setting().
        $this->app->singleton(SettingsService::class);

        // Intervention Image v3 sin el paquete de Laravel: se registra el
        // manager a mano para no anadir otra dependencia solo por el facade.
        // GD esta verificada en esta maquina (docs/09_DEPLOYMENT.md).
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(new Driver));
    }

    public function boot(): void
    {
        //
    }
}
