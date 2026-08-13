<?php

namespace App\Modules\Media\Livewire;

use App\Modules\Media\Models\MediaFile;
use App\Modules\Media\Services\MediaLibraryService;
use App\Rules\RealImage;
use App\Support\Concerns\NotifiesInline;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MediaManager extends Component
{
    use NotifiesInline, WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $context = '';

    #[Url(except: 'grid')]
    public string $view = 'grid';

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public ?int $editingId = null;

    public string $editAlt = '';

    public string $editTitle = '';

    /** Confirmacion de borrado: id + lista de usos encontrados */
    public ?int $confirmingId = null;

    /** @var list<string> */
    public array $confirmingUsages = [];

    public const CONTEXTS = ['news', 'agent', 'page', 'banner', 'general'];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'context'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'uploads.*' => [
                'file', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                new RealImage(allowSvg: false, minSize: 200),
            ],
        ];
    }

    public function updatedUploads(): void
    {
        $this->validate();

        $service = app(MediaLibraryService::class);
        $subidas = 0;

        foreach ($this->uploads as $archivo) {
            try {
                $service->store($archivo, $this->context ?: 'general');
                $subidas++;
            } catch (ValidationException $e) {
                $this->notifyError(collect($e->errors())->flatten()->first());
            }
        }

        $this->uploads = [];

        if ($subidas > 0) {
            $this->notify(__('admin/media.uploaded', ['count' => $subidas]));
            $this->resetPage();
        }
    }

    // ------------------------------------------------------------------
    // Edicion
    // ------------------------------------------------------------------

    public function edit(int $id): void
    {
        $media = MediaFile::findOrFail($id);

        $this->editingId = $id;
        $this->editAlt = (string) $media->alt_text;
        $this->editTitle = (string) $media->title;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editAlt' => ['nullable', 'string', 'max:255'],
            'editTitle' => ['nullable', 'string', 'max:255'],
        ]);

        MediaFile::findOrFail($this->editingId)->update([
            'alt_text' => $this->editAlt ?: null,
            'title' => $this->editTitle ?: null,
        ]);

        $this->editingId = null;
        $this->notify(__('admin/media.saved'));
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    // ------------------------------------------------------------------
    // Borrado con verificacion de uso
    // ------------------------------------------------------------------

    /**
     * No borra directamente: primero mira donde se esta usando y lo enseña.
     * Borrar un logo en uso deja un hueco en todas las paginas.
     */
    public function confirmDelete(int $id, MediaLibraryService $service): void
    {
        $media = MediaFile::findOrFail($id);

        $this->confirmingId = $id;
        $this->confirmingUsages = $service->usages($media);
    }

    public function delete(MediaLibraryService $service): void
    {
        $media = MediaFile::findOrFail($this->confirmingId);

        $service->delete($media);

        $this->confirmingId = null;
        $this->confirmingUsages = [];

        $this->notify(__('admin/media.deleted'));
    }

    public function cancelDelete(): void
    {
        $this->confirmingId = null;
        $this->confirmingUsages = [];
    }

    public function render(): View
    {
        return view('livewire.admin.media-manager', [
            'files' => MediaFile::query()
                ->search($this->search)
                ->context($this->context ?: null)
                ->latest()
                ->paginate($this->view === 'grid' ? 24 : 20),
            'contexts' => self::CONTEXTS,
        ]);
    }
}
