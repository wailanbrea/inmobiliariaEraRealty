<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\MediaFile;
use App\Modules\News\Models\NewsPost;
use App\Modules\Settings\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class MediaLibraryService
{
    private const DISK = 'public';

    private const MAX_WIDTH = 1600;

    public function __construct(private ImageManager $images) {}

    /**
     * Sube un archivo a la biblioteca.
     *
     * @throws ValidationException
     */
    public function store(UploadedFile $file, ?string $context = null): MediaFile
    {
        $carpeta = 'media/'.now()->format('Y/m');
        $nombre = Str::random(16);

        try {
            $imagen = $this->images->read($file->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'file' => "No se pudo procesar «{$file->getClientOriginalName()}».",
            ]);
        }

        // Igual que en las propiedades: orientar y descartar el EXIF.
        $imagen->orient();

        if ($imagen->width() > self::MAX_WIDTH) {
            $imagen->scaleDown(width: self::MAX_WIDTH);
        }

        $rutaOriginal = "{$carpeta}/{$nombre}.jpg";
        Storage::disk(self::DISK)->put($rutaOriginal, (string) $imagen->toJpeg(quality: 85));

        $rutaWebp = "{$carpeta}/{$nombre}.webp";
        Storage::disk(self::DISK)->put($rutaWebp, (string) $imagen->toWebp(quality: 82));

        $miniatura = $this->images->read((string) $imagen->toJpeg(quality: 90))
            ->cover(400, 300);

        $rutaThumb = "{$carpeta}/{$nombre}_thumb.jpg";
        Storage::disk(self::DISK)->put($rutaThumb, (string) $miniatura->toJpeg(quality: 80));

        return MediaFile::create([
            'disk' => self::DISK,
            'path' => $rutaOriginal,
            'webp_path' => $rutaWebp,
            'thumbnail_path' => $rutaThumb,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'size' => Storage::disk(self::DISK)->size($rutaOriginal),
            'width' => $imagen->width(),
            'height' => $imagen->height(),
            'context' => $context,
            'folder' => $carpeta,
            'uploaded_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * ¿Donde se esta usando este archivo?
     *
     * Se comprueba antes de borrar para no dejar un hueco en el sitio.
     * Se mira en settings (logo, favicon, OG) y en las noticias, que son los
     * consumidores de la biblioteca. Las imagenes de propiedad no viven aqui.
     *
     * @return list<string>
     */
    public function usages(MediaFile $media): array
    {
        $usos = [];

        $enSettings = Setting::where('value', $media->path)->pluck('key');

        foreach ($enSettings as $clave) {
            $usos[] = __('admin/media.used_in_setting', ['key' => $clave]);
        }

        // Las noticias llegan en la Fase 6; el gancho queda listo.
        if (class_exists(NewsPost::class)) {
            $enNoticias = NewsPost::where('featured_image', $media->path)->count();

            if ($enNoticias > 0) {
                $usos[] = __('admin/media.used_in_news', ['count' => $enNoticias]);
            }
        }

        return $usos;
    }

    public function isInUse(MediaFile $media): bool
    {
        return $this->usages($media) !== [];
    }

    /**
     * Borra el registro y sus ficheros. No comprueba el uso: eso lo decide
     * quien llama, para poder mostrar antes donde se esta usando.
     */
    public function delete(MediaFile $media): void
    {
        $rutas = $media->allPaths();
        $disco = $media->disk;

        $media->delete();

        Storage::disk($disco)->delete($rutas);
    }

    /**
     * Ficheros que estan en disco pero no en la base de datos.
     *
     * Solo LISTA: el borrado lo confirma una persona. Un huerfano puede ser
     * un archivo legitimo subido por otra via.
     *
     * @return list<string>
     */
    public function findOrphans(): array
    {
        $enDisco = collect(Storage::disk(self::DISK)->allFiles('media'))
            ->reject(fn (string $ruta) => str_ends_with($ruta, '.gitkeep'));

        $enBd = MediaFile::query()
            ->get(['path', 'thumbnail_path', 'webp_path'])
            ->flatMap(fn (MediaFile $m) => $m->allPaths())
            ->unique()
            ->flip();

        return $enDisco->reject(fn (string $ruta) => $enBd->has($ruta))->values()->all();
    }

    /**
     * Registros cuyo fichero ya no existe en disco.
     *
     * @return list<int>
     */
    public function findMissingFiles(): array
    {
        return MediaFile::all()
            ->reject(fn (MediaFile $m) => Storage::disk($m->disk)->exists($m->path))
            ->pluck('id')
            ->all();
    }
}
