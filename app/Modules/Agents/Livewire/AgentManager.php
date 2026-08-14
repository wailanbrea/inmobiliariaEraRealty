<?php

namespace App\Modules\Agents\Livewire;

use App\Modules\Agents\Models\Agent;
use App\Modules\Properties\Models\Property;
use App\Modules\Settings\Services\SettingsImageService;
use App\Rules\RealImage;
use App\Support\Concerns\NotifiesInline;
use App\Support\Locale;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Gestion de asesores.
 *
 * El cargo y la biografia son traducibles (JSON en la propia tabla); el resto
 * de datos son de contacto y no se traducen.
 */
class AgentManager extends Component
{
    use NotifiesInline, WithFileUploads;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $name = '';

    /** @var array<string, string> */
    public array $position = [];

    /** @var array<string, string> */
    public array $bio = [];

    public string $phone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $social_instagram = '';

    public string $social_linkedin = '';

    public bool $is_active = true;

    public ?TemporaryUploadedFile $photo = null;

    /** Confirmacion de borrado con recuento de propiedades asignadas. */
    public ?int $confirmingId = null;

    public int $confirmingProperties = 0;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->position = array_fill_keys(Locale::codes(), '');
        $this->bio = array_fill_keys(Locale::codes(), '');
        $this->phone = '';
        $this->whatsapp = '';
        $this->email = '';
        $this->social_instagram = '';
        $this->social_linkedin = '';
        $this->is_active = true;
        $this->photo = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $agente = Agent::findOrFail($id);

        $this->editingId = $id;
        $this->name = $agente->name;
        $this->phone = (string) $agente->phone;
        $this->whatsapp = (string) $agente->whatsapp;
        $this->email = (string) $agente->email;
        $this->social_instagram = (string) $agente->social_instagram;
        $this->social_linkedin = (string) $agente->social_linkedin;
        $this->is_active = (bool) $agente->is_active;
        $this->photo = null;

        foreach (['position', 'bio'] as $campo) {
            $bruto = json_decode($agente->getAttributes()[$campo] ?? '{}', true) ?: [];

            foreach (Locale::codes() as $codigo) {
                $this->{$campo}[$codigo] = $bruto[$codigo] ?? '';
            }
        }

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
        $reglas = [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'photo' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                new RealImage(allowSvg: false, minSize: 300),
            ],
        ];

        foreach (Locale::codes() as $codigo) {
            $reglas["position.{$codigo}"] = ['nullable', 'string', 'max:100'];
            $reglas["bio.{$codigo}"] = ['nullable', 'string', 'max:1000'];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre del asesor es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'photo.max' => 'La foto supera los 5 MB permitidos.',
        ];
    }

    public function save(SettingsImageService $images): void
    {
        $this->authorize('create', Property::class);

        $this->validate();

        $datos = [
            'name' => $this->name,
            'position' => array_filter($this->position, fn ($v) => filled($v)) ?: null,
            'bio' => array_filter($this->bio, fn ($v) => filled($v)) ?: null,
            'phone' => $this->phone ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'email' => $this->email ?: null,
            'social_instagram' => $this->social_instagram ?: null,
            'social_linkedin' => $this->social_linkedin ?: null,
            'is_active' => $this->is_active,
        ];

        $agente = $this->editingId
            ? Agent::findOrFail($this->editingId)
            : new Agent(['sort_order' => (Agent::max('sort_order') ?? -1) + 1]);

        if ($this->photo) {
            $anterior = $agente->photo;

            // Cuadrada: las fichas del equipo y del detalle usan formato 1:1.
            $datos['photo'] = $images->storeFor('agents', $this->photo, maxWidth: 800);

            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }
        }

        $agente->fill($datos)->save();

        $this->showForm = false;
        $this->resetForm();

        $this->notify(__('admin/agents.saved'));
    }

    public function toggleActive(int $id): void
    {
        $agente = Agent::findOrFail($id);
        $agente->update(['is_active' => ! $agente->is_active]);
    }

    public function move(int $id, string $direction): void
    {
        $agente = Agent::findOrFail($id);

        $vecino = Agent::query()
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('sort_order', '<', $agente->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $agente->sort_order)->orderBy('sort_order'),
            )
            ->first();

        if (! $vecino) {
            return;
        }

        $orden = $agente->sort_order;
        $agente->update(['sort_order' => $vecino->sort_order]);
        $vecino->update(['sort_order' => $orden]);
    }

    public function removePhoto(int $id): void
    {
        $agente = Agent::findOrFail($id);

        if ($agente->photo) {
            Storage::disk('public')->delete($agente->photo);
            $agente->update(['photo' => null]);
        }

        $this->notify(__('admin/agents.photo_removed'));
    }

    /**
     * Antes de borrar se cuenta cuantas propiedades tiene asignadas: al
     * eliminarlo esas fichas se quedan sin asesor visible.
     */
    public function confirmDelete(int $id): void
    {
        $this->confirmingId = $id;
        $this->confirmingProperties = Agent::findOrFail($id)->properties()->count();
    }

    public function delete(): void
    {
        $agente = Agent::findOrFail($this->confirmingId);

        if ($agente->photo) {
            Storage::disk('public')->delete($agente->photo);
        }

        // agent_id queda a NULL por la clave foranea: las propiedades no se
        // borran, solo se quedan sin asesor asignado.
        $agente->delete();

        $this->confirmingId = null;
        $this->confirmingProperties = 0;

        $this->notify(__('admin/agents.deleted'));
    }

    public function cancelDelete(): void
    {
        $this->confirmingId = null;
        $this->confirmingProperties = 0;
    }

    public function render(): View
    {
        return view('livewire.admin.agent-manager', [
            'agents' => Agent::withCount('properties')->orderBy('sort_order')->get(),
            'locales' => Locale::supported(),
        ]);
    }
}
