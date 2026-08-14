<?php

namespace App\Modules\Properties\Livewire;

use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Modules\Agents\Models\Agent;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de propiedades del panel, con filtros en vivo.
 *
 * Los filtros van en la URL para que un listado filtrado se pueda compartir
 * y sobreviva a un refresco.
 */
class PropertyIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $operation = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $province = '';

    #[Url(except: '')]
    public string $agent = '';

    #[Url(except: '')]
    public string $flag = '';        // featured | investment | project

    #[Url(except: false)]
    public bool $trashed = false;

    #[Url(except: 'recent')]
    public string $sort = 'recent';

    /** @var list<int> */
    public array $selected = [];

    public bool $selectAll = false;

    public function updated(string $property): void
    {
        // Cualquier cambio de filtro vuelve a la primera pagina: quedarse en
        // la 7 tras filtrar produce una pagina vacia y parece un error.
        if (! in_array($property, ['selected', 'selectAll'], true)) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->query()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'operation', 'type', 'province', 'agent', 'flag', 'trashed']);
        $this->resetPage();
    }

    // ------------------------------------------------------------------
    // Acciones en lote
    // ------------------------------------------------------------------

    public function bulkPublish(PropertyService $service): void
    {
        $hechas = 0;

        foreach ($this->selectedProperties() as $property) {
            if (! auth()->user()->can('publish', $property)) {
                continue;
            }

            // Sin titulo en el idioma por defecto no se publica: dejaria una
            // ficha rota en el sitio publico.
            if (blank($property->translated(Locale::default())?->title)) {
                continue;
            }

            $service->publish($property);
            $hechas++;
        }

        $this->finishBulk(__('admin/properties.bulk_published', ['count' => $hechas]));
    }

    public function bulkPause(PropertyService $service): void
    {
        $hechas = 0;

        foreach ($this->selectedProperties() as $property) {
            if (auth()->user()->can('publish', $property)) {
                $service->pause($property);
                $hechas++;
            }
        }

        $this->finishBulk(__('admin/properties.bulk_paused', ['count' => $hechas]));
    }

    public function bulkFeature(bool $value = true): void
    {
        $hechas = 0;

        foreach ($this->selectedProperties() as $property) {
            if (auth()->user()->can('update', $property)) {
                $property->update(['is_featured' => $value]);
                $hechas++;
            }
        }

        $this->finishBulk(__('admin/properties.bulk_featured', ['count' => $hechas]));
    }

    public function bulkDelete(): void
    {
        $hechas = 0;

        foreach ($this->selectedProperties() as $property) {
            if (auth()->user()->can('delete', $property)) {
                $property->delete();
                $hechas++;
            }
        }

        $this->finishBulk(__('admin/properties.bulk_deleted', ['count' => $hechas]));
    }

    public function restore(int $id): void
    {
        $property = Property::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $property);

        $property->restore();

        session()->flash('status', __('admin/properties.restored'));
    }

    private function finishBulk(string $message): void
    {
        $this->selected = [];
        $this->selectAll = false;

        session()->flash('status', $message);
    }

    /** @return Collection<int, Property> */
    private function selectedProperties()
    {
        return Property::withTrashed()
            ->with('translations')
            ->whereIn('id', $this->selected)
            ->get();
    }

    // ------------------------------------------------------------------
    // Consulta
    // ------------------------------------------------------------------

    private function query(): Builder
    {
        $locale = Locale::current();

        return Property::query()
            ->when($this->trashed, fn (Builder $q) => $q->onlyTrashed())
            ->with(['translations', 'type', 'city', 'sector', 'province', 'agent', 'mainImage'])

            ->when($this->search, function (Builder $q) {
                $termino = trim($this->search);

                $q->where(function (Builder $sub) use ($termino) {
                    $sub->where('reference_code', 'like', "%{$termino}%")
                        ->orWhereHas('translations', fn (Builder $t) => $t->where('title', 'like', "%{$termino}%"));
                });
            })

            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->operation, fn (Builder $q) => $q->where('operation_type', $this->operation))
            ->when($this->type, fn (Builder $q) => $q->where('property_type_id', $this->type))
            ->when($this->province, fn (Builder $q) => $q->where('province_id', $this->province))
            ->when($this->agent, fn (Builder $q) => $q->where('agent_id', $this->agent))

            ->when($this->flag === 'featured', fn (Builder $q) => $q->where('is_featured', true))
            ->when($this->flag === 'investment', fn (Builder $q) => $q->where('is_investment', true))
            ->when($this->flag === 'project', fn (Builder $q) => $q->where('is_project', true))

            // Un agente solo ve lo suyo, tambien en el listado. La policy
            // protege cada ficha, pero el listado no debe ni mostrarlas.
            ->when(
                auth()->user()->hasRole('agent'),
                fn (Builder $q) => $q->where('agent_id', auth()->user()->agent?->id ?? 0)
            )

            ->when($this->sort === 'recent', fn (Builder $q) => $q->latest('id'))
            ->when($this->sort === 'oldest', fn (Builder $q) => $q->oldest('id'))
            ->when($this->sort === 'price_asc', fn (Builder $q) => $q->orderByRaw('price IS NULL, price ASC'))
            ->when($this->sort === 'price_desc', fn (Builder $q) => $q->orderByRaw('price IS NULL, price DESC'))
            ->when($this->sort === 'views', fn (Builder $q) => $q->orderByDesc('views_count'));
    }

    public function render(): View
    {
        return view('livewire.admin.property-index', [
            'properties' => $this->query()->paginate(20),
            'types' => PropertyType::active()->get(),
            'provinces' => Province::active()->get(),
            'agents' => Agent::active()->get(),
            'statuses' => PropertyStatus::cases(),
            'operations' => OperationType::cases(),
            'totalTrashed' => Property::onlyTrashed()->count(),
        ]);
    }
}
