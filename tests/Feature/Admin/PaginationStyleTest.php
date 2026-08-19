<?php

use App\Modules\Properties\Models\Property;
use App\Modules\PropertyTypes\Models\PropertyType;
use Database\Seeders\PropertyTypeSeeder;

/*
|--------------------------------------------------------------------------
| El panel pagina con el estilo del sitio
|--------------------------------------------------------------------------
| Livewire cambia Paginator::$defaultView por la suya mientras renderiza el
| componente. Sin App\Support\Concerns\UsesSitePagination el panel salia con
| el diseno de serie y con el texto en ingles. Ver el trait.
*/

beforeEach(function () {
    $this->seed(PropertyTypeSeeder::class);
    $this->admin = userWithRole('admin');
});

it('usa la paginacion del sitio en el listado de propiedades', function () {
    Property::factory()->count(25)->create([
        'property_type_id' => PropertyType::first()->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.properties.index'))
        ->assertOk()
        ->assertSee(__('pagination.navigation'))
        ->assertSee(__('pagination.next'))
        ->assertDontSee('Showing');
});

it('la vista publicada es la que resuelve el namespace pagination', function () {
    expect(view()->exists('pagination::tailwind'))->toBeTrue()
        ->and(realpath(view('pagination::tailwind')->getPath()))
        ->toBe(realpath(resource_path('views/vendor/pagination/tailwind.blade.php')));
});
