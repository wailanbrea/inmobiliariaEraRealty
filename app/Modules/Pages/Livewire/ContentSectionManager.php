<?php

namespace App\Modules\Pages\Livewire;

use App\Modules\Pages\Models\ContentSection;
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
 * Edicion de los bloques de contenido de una pagina.
 *
 * Es lo que hace administrable el inicio: titular del hero, su imagen de
 * fondo, los rotulos de cada seccion y el CTA final.
 */
class ContentSectionManager extends Component
{
    use NotifiesInline, WithFileUploads;

    public string $pageKey = 'home';

    public ?int $editingId = null;

    /** @var array<string, array<string, string>> [locale => campos] */
    public array $fields = [];

    public string $buttonUrl = '';

    public ?TemporaryUploadedFile $image = null;

    /** Ancho maximo recomendado para la imagen del hero. */
    public const HERO_MIN_WIDTH = 1200;

    public function mount(string $pageKey = 'home'): void
    {
        $this->pageKey = $pageKey;
    }

    public function edit(int $id): void
    {
        $seccion = ContentSection::with('translations')->findOrFail($id);

        $this->editingId = $id;
        $this->buttonUrl = (string) $seccion->button_url;
        $this->image = null;
        $this->fields = [];

        foreach (Locale::codes() as $codigo) {
            $t = $seccion->translations->firstWhere('locale', $codigo);

            $this->fields[$codigo] = [
                'title' => (string) $t?->title,
                'subtitle' => (string) $t?->subtitle,
                'content' => (string) $t?->content,
                'button_text' => (string) $t?->button_text,
            ];
        }

        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->fields = [];
        $this->image = null;
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $reglas = [
            'buttonUrl' => ['nullable', 'string', 'max:255'],
            'image' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                new RealImage(allowSvg: false, minSize: 600),
            ],
        ];

        foreach (Locale::codes() as $codigo) {
            $reglas["fields.{$codigo}.title"] = ['nullable', 'string', 'max:200'];
            $reglas["fields.{$codigo}.subtitle"] = ['nullable', 'string', 'max:300'];
            $reglas["fields.{$codigo}.content"] = ['nullable', 'string', 'max:5000'];
            $reglas["fields.{$codigo}.button_text"] = ['nullable', 'string', 'max:100'];
        }

        return $reglas;
    }

    public function save(SettingsImageService $images): void
    {
        $this->authorize('create', Property::class);

        $this->validate();

        $seccion = ContentSection::findOrFail($this->editingId);

        if ($this->image) {
            // Se reutiliza el pipeline de imagenes de configuracion: orienta,
            // descarta el EXIF, reduce y guarda en WebP.
            $anterior = $seccion->image;

            $ruta = $images->storeFor(
                'content_sections',
                $this->image,
                maxWidth: 1920,
            );

            $seccion->image = $ruta;

            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }
        }

        $seccion->button_url = $this->buttonUrl ?: null;
        $seccion->save();

        foreach ($this->fields as $codigo => $campos) {
            if (! Locale::isSupported($codigo)) {
                continue;
            }

            $seccion->translations()->updateOrCreate(
                ['locale' => $codigo],
                [
                    'title' => $campos['title'] ?: null,
                    'subtitle' => $campos['subtitle'] ?: null,
                    'content' => $campos['content'] ?: null,
                    'button_text' => $campos['button_text'] ?: null,
                ],
            );
        }

        ContentSection::flushCache($this->pageKey);

        $this->cancel();
        $this->notify(__('admin/content.saved'));
    }

    public function removeImage(int $id): void
    {
        $seccion = ContentSection::findOrFail($id);

        if ($seccion->image) {
            Storage::disk('public')->delete($seccion->image);
            $seccion->update(['image' => null]);
            ContentSection::flushCache($this->pageKey);
        }

        $this->notify(__('admin/content.image_removed'));
    }

    public function toggleActive(int $id): void
    {
        $seccion = ContentSection::findOrFail($id);
        $seccion->update(['is_active' => ! $seccion->is_active]);

        ContentSection::flushCache($this->pageKey);
    }

    public function render(): View
    {
        return view('livewire.admin.content-section-manager', [
            'sections' => ContentSection::with('translations')
                ->where('page_key', $this->pageKey)
                ->orderBy('sort_order')
                ->get(),
            'locales' => Locale::supported(),
        ]);
    }
}
