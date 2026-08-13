<?php

namespace App\Modules\Properties\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Modules\Agents\Models\Agent;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Requests\PropertyRequest;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(private PropertyService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', Property::class);

        // El listado con filtros lo sirve el componente Livewire.
        return view('admin.properties.index');
    }

    public function create(): View
    {
        $this->authorize('create', Property::class);

        $property = new Property([
            'status' => PropertyStatus::Draft,
            'currency' => Currency::USD,
            'operation_type' => OperationType::Sale,
        ]);

        return view('admin.properties.form', $this->formData($property));
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        $property = $this->service->create(
            $request->propertyData(),
            $request->translationsData(),
            $request->amenityIds(),
        );

        return redirect()
            ->route('admin.properties.edit', $property)
            ->with('status', __('admin/properties.created'));
    }

    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        $property->load(['translations', 'amenities']);

        return view('admin.properties.form', $this->formData($property));
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        $this->service->update(
            $property,
            $request->propertyData(),
            $request->translationsData(),
            $request->amenityIds(),
        );

        return back()->with('status', __('admin/properties.saved'));
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        // Soft delete: los ficheros de imagen se conservan porque la accion
        // es reversible. Ver docs/05_MEDIA_UPLOADS.md seccion 8.
        $property->delete();

        return redirect()
            ->route('admin.properties.index')
            ->with('status', __('admin/properties.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $property = Property::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $property);

        $property->restore();

        return back()->with('status', __('admin/properties.restored'));
    }

    public function publish(Property $property): RedirectResponse
    {
        $this->authorize('publish', $property);

        // Publicar sin titulo en el idioma por defecto dejaria una ficha rota
        // en el sitio publico.
        if (blank($property->translated(Locale::default())?->title)) {
            return back()->withErrors([
                'publish' => __('admin/properties.cannot_publish_without_title'),
            ]);
        }

        $this->service->publish($property);

        return back()->with('status', __('admin/properties.published'));
    }

    public function pause(Property $property): RedirectResponse
    {
        $this->authorize('publish', $property);

        $this->service->pause($property);

        return back()->with('status', __('admin/properties.paused'));
    }

    public function changeStatus(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $status = PropertyStatus::tryFrom((string) $request->input('status'));

        abort_if($status === null, 422);

        $this->service->changeStatus($property, $status);

        return back()->with('status', __('admin/properties.status_changed'));
    }

    /**
     * Vista previa de una ficha aunque este en borrador.
     * El enlace va firmado y caduca en 30 minutos.
     */
    public function preview(Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $slug = $property->translated()?->slug;

        if ($slug === null) {
            return back()->withErrors(['preview' => __('admin/properties.cannot_preview_without_title')]);
        }

        return redirect()->to(
            URL::temporarySignedRoute(
                Locale::current().'.properties.show',
                now()->addMinutes(30),
                ['slug' => $slug],
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Property $property): array
    {
        return [
            'property' => $property,
            'locales' => Locale::supported(),
            'types' => PropertyType::active()->get(),
            'provinces' => Province::active()->get(),
            'agents' => Agent::active()->get(),
            'amenities' => Amenity::active()->get()->groupBy('category'),
            'statuses' => PropertyStatus::cases(),
            'operations' => OperationType::cases(),
            'currencies' => Currency::cases(),
            'selectedAmenities' => $property->exists
                ? $property->amenities->pluck('id')->all()
                : [],
        ];
    }
}
