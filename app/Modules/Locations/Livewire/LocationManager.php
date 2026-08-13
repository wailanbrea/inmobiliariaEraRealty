<?php

namespace App\Modules\Locations\Livewire;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Support\Concerns\NotifiesInline;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Arbol de ubicaciones: provincia -> ciudad -> sector.
 *
 * Se edita en linea en lugar de con tres CRUD separados: la jerarquia es lo
 * que importa, y saltar entre tres pantallas para anadir un sector seria
 * absurdo.
 */
class LocationManager extends Component
{
    use NotifiesInline;

    public ?int $openProvince = null;

    public ?int $openCity = null;

    public string $search = '';

    /** Formulario en linea: ['province'|'city'|'sector', idPadre] */
    public ?string $addingType = null;

    public ?int $addingParent = null;

    public string $newName = '';

    /** Edicion en linea */
    public ?string $editingType = null;

    public ?int $editingId = null;

    public string $editName = '';

    public function toggleProvince(int $id): void
    {
        $this->openProvince = $this->openProvince === $id ? null : $id;
        $this->openCity = null;
        $this->cancelForms();
    }

    public function toggleCity(int $id): void
    {
        $this->openCity = $this->openCity === $id ? null : $id;
        $this->cancelForms();
    }

    // ------------------------------------------------------------------
    // Alta
    // ------------------------------------------------------------------

    public function startAdd(string $type, ?int $parentId = null): void
    {
        $this->addingType = $type;
        $this->addingParent = $parentId;
        $this->newName = '';
        $this->editingType = null;
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->validate(
            ['newName' => ['required', 'string', 'max:100']],
            ['newName.required' => 'Escribe un nombre.'],
        );

        $slug = Str::slug($this->newName);

        match ($this->addingType) {
            'province' => Province::firstOrCreate(
                ['slug' => $slug],
                ['name' => $this->newName, 'is_active' => true,
                    'sort_order' => Province::max('sort_order') + 1],
            ),
            'city' => City::firstOrCreate(
                ['province_id' => $this->addingParent, 'slug' => $slug],
                ['name' => $this->newName, 'is_active' => true,
                    'sort_order' => City::where('province_id', $this->addingParent)->max('sort_order') + 1],
            ),
            'sector' => Sector::firstOrCreate(
                ['city_id' => $this->addingParent, 'slug' => $slug],
                ['name' => $this->newName, 'is_active' => true,
                    'sort_order' => Sector::where('city_id', $this->addingParent)->max('sort_order') + 1],
            ),
            default => null,
        };

        $this->cancelForms();
        $this->notify(__('admin/catalog.created'));
    }

    // ------------------------------------------------------------------
    // Edicion
    // ------------------------------------------------------------------

    public function startEdit(string $type, int $id, string $name): void
    {
        $this->editingType = $type;
        $this->editingId = $id;
        $this->editName = $name;
        $this->addingType = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate(
            ['editName' => ['required', 'string', 'max:100']],
            ['editName.required' => 'Escribe un nombre.'],
        );

        // El slug NO se regenera al renombrar: aparece en las URL de los
        // filtros publicos y cambiarlo rompe los enlaces ya compartidos.
        $this->modelFor($this->editingType)::findOrFail($this->editingId)
            ->update(['name' => $this->editName]);

        $this->cancelForms();
        $this->notify(__('admin/catalog.updated'));
    }

    public function toggleActive(string $type, int $id): void
    {
        $modelo = $this->modelFor($type)::findOrFail($id);
        $modelo->update(['is_active' => ! $modelo->is_active]);
    }

    /**
     * Solo se borra si no hay propiedades ni descendientes.
     * Borrar una provincia con ciudades arrastraria en cascada todo su arbol.
     */
    public function delete(string $type, int $id): void
    {
        $modelo = $this->modelFor($type)::findOrFail($id);

        if ($modelo->properties()->count() > 0) {
            $this->notifyError(__('admin/catalog.in_use', [
                'count' => $modelo->properties()->count(),
            ]));

            return;
        }

        $hijos = match ($type) {
            'province' => $modelo->cities()->count(),
            'city' => $modelo->sectors()->count(),
            default => 0,
        };

        if ($hijos > 0) {
            $this->notifyError(__('admin/catalog.has_children', ['count' => $hijos]));

            return;
        }

        $modelo->delete();

        $this->notify(__('admin/catalog.deleted'));
    }

    public function cancelForms(): void
    {
        $this->addingType = null;
        $this->addingParent = null;
        $this->newName = '';
        $this->editingType = null;
        $this->editingId = null;
        $this->editName = '';
        $this->resetValidation();
    }

    /** @return class-string */
    private function modelFor(string $type): string
    {
        return match ($type) {
            'province' => Province::class,
            'city' => City::class,
            'sector' => Sector::class,
        };
    }

    public function render(): View
    {
        $provincias = Province::query()
            ->withCount('cities')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();

        $ciudades = $this->openProvince
            ? City::where('province_id', $this->openProvince)
                ->withCount('sectors')->orderBy('name')->get()
            : collect();

        $sectores = $this->openCity
            ? Sector::where('city_id', $this->openCity)->orderBy('name')->get()
            : collect();

        return view('livewire.admin.location-manager', compact('provincias', 'ciudades', 'sectores'));
    }
}
