<?php

namespace App\Modules\PropertyTypes\Livewire;

use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Support\Concerns\NotifiesInline;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Gestion de los catalogos traducibles: tipos de propiedad y amenidades.
 *
 * Los dos comparten estructura (nombre por idioma, slug, icono, activo,
 * orden), asi que comparten componente. La unica diferencia es que las
 * amenidades tienen categoria.
 */
class CatalogManager extends Component
{
    use NotifiesInline;

    /** 'property-types' | 'amenities' */
    public string $catalog = 'property-types';

    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, string> */
    public array $name = [];

    public string $slug = '';

    public string $icon = '';

    public string $category = '';

    public bool $is_active = true;

    public function mount(string $catalog = 'property-types'): void
    {
        $this->catalog = $catalog;
        $this->resetForm();
    }

    public function isAmenities(): bool
    {
        return $this->catalog === 'amenities';
    }

    /** @return class-string<Model> */
    private function modelClass(): string
    {
        return $this->isAmenities() ? Amenity::class : PropertyType::class;
    }

    // ------------------------------------------------------------------
    // Formulario
    // ------------------------------------------------------------------

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = array_fill_keys(Locale::codes(), '');
        $this->slug = '';
        $this->icon = '';
        $this->category = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $modelo = $this->modelClass()::findOrFail($id);

        $this->editingId = $id;

        $raw = $modelo->getAttributes()['name'] ?? '{}';
        $decoded = json_decode($raw, true) ?: [];

        foreach (Locale::codes() as $code) {
            $this->name[$code] = $decoded[$code] ?? '';
        }

        $this->slug = $modelo->slug;
        $this->icon = (string) $modelo->icon;
        $this->category = (string) ($modelo->category ?? '');
        $this->is_active = (bool) $modelo->is_active;

        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $tabla = $this->isAmenities() ? 'amenities' : 'property_types';

        $rules = [
            'slug' => [
                'nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/',
                'unique:'.$tabla.',slug'.($this->editingId ? ','.$this->editingId : ''),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];

        foreach (Locale::codes() as $code) {
            // El idioma por defecto es obligatorio; el otro cae a el si falta.
            $rules["name.{$code}"] = [
                $code === Locale::default() ? 'required' : 'nullable',
                'string', 'max:100',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.es.required' => 'El nombre en español es obligatorio.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'slug.unique' => 'Ese slug ya está en uso.',
        ];
    }

    public function save(): void
    {
        $this->authorize('create', Property::class);

        $datos = $this->validate();

        $slug = filled($this->slug)
            ? Str::slug($this->slug)
            : Str::slug($this->name[Locale::default()]);

        $atributos = [
            'name' => array_filter($this->name, fn ($v) => filled($v)),
            'slug' => $slug,
            'icon' => $this->icon ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isAmenities()) {
            $atributos['category'] = $this->category ?: null;
        }

        if ($this->editingId) {
            $this->modelClass()::findOrFail($this->editingId)->update($atributos);
            $mensaje = __('admin/catalog.updated');
        } else {
            $atributos['sort_order'] = $this->modelClass()::max('sort_order') + 1;
            $this->modelClass()::create($atributos);
            $mensaje = __('admin/catalog.created');
        }

        $this->showForm = false;
        $this->resetForm();

        $this->notify($mensaje);
    }

    // ------------------------------------------------------------------
    // Acciones
    // ------------------------------------------------------------------

    public function toggleActive(int $id): void
    {
        $modelo = $this->modelClass()::findOrFail($id);
        $modelo->update(['is_active' => ! $modelo->is_active]);
    }

    /**
     * Borra solo si nadie lo usa. Si hay propiedades asociadas se dice
     * cuantas, en vez de dar un error opaco de clave foranea.
     */
    public function delete(int $id): void
    {
        $modelo = $this->modelClass()::findOrFail($id);

        $enUso = $modelo->properties()->count();

        if ($enUso > 0) {
            $this->notifyError(__('admin/catalog.in_use', ['count' => $enUso]));

            return;
        }

        $modelo->delete();

        $this->notify(__('admin/catalog.deleted'));
    }

    public function move(int $id, string $direction): void
    {
        $modelo = $this->modelClass()::findOrFail($id);

        $vecino = $this->modelClass()::query()
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('sort_order', '<', $modelo->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $modelo->sort_order)->orderBy('sort_order'),
            )
            ->first();

        if (! $vecino) {
            return;
        }

        $orden = $modelo->sort_order;
        $modelo->update(['sort_order' => $vecino->sort_order]);
        $vecino->update(['sort_order' => $orden]);
    }

    public function render(): View
    {
        $items = $this->modelClass()::query()
            ->withCount('properties')
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.catalog-manager', [
            'items' => $items,
            'locales' => Locale::supported(),
        ]);
    }
}
