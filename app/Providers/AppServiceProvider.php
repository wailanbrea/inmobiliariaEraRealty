<?php

namespace App\Providers;

use App\Modules\Agents\Livewire\AgentManager;
use App\Modules\Audit\Livewire\AuditLogIndex;
use App\Modules\Audit\Observers\NewsPostObserver;
use App\Modules\Audit\Observers\PropertyImageObserver;
use App\Modules\Audit\Observers\PropertyObserver;
use App\Modules\Locations\Livewire\LocationManager;
use App\Modules\Media\Livewire\MediaManager;
use App\Modules\News\Models\NewsPost;
use App\Modules\Pages\Livewire\ContentSectionManager;
use App\Modules\Properties\Livewire\PropertyIndex;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Policies\PropertyPolicy;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Livewire\CatalogManager;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Livewire;

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
        // Los modelos viven en app/Modules/*/Models, no en app/Models, asi que
        // la resolucion automatica de Laravel construye un nombre de factory
        // que no existe. Se mapea por el nombre corto de la clase.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Las policies viven en los modulos, asi que no las descubre la
        // convencion App\Policies\*. Se registran a mano.
        Gate::policy(Property::class, PropertyPolicy::class);

        // Auditoria por observer, no por controlador: asi queda registrado
        // venga de donde venga la escritura. Ver app/Enums/AuditAction.php.
        Property::observe(PropertyObserver::class);
        PropertyImage::observe(PropertyImageObserver::class);
        NewsPost::observe(NewsPostObserver::class);

        // Livewire tampoco descubre componentes fuera de app/Livewire.
        Livewire::component('property-index', PropertyIndex::class);
        Livewire::component('catalog-manager', CatalogManager::class);
        Livewire::component('location-manager', LocationManager::class);
        Livewire::component('media-manager', MediaManager::class);
        Livewire::component('content-section-manager', ContentSectionManager::class);
        Livewire::component('agent-manager', AgentManager::class);
        Livewire::component('audit-log-index', AuditLogIndex::class);
    }
}
