<?php

namespace App\Modules\Settings\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

/**
 * Subida de las imagenes de configuracion: logo, logo oscuro, favicon y la
 * imagen Open Graph por defecto.
 *
 * Aplica las cuatro capas de validacion de docs/05_MEDIA_UPLOADS.md. La cuarta
 * (decodificar y volver a codificar con Intervention) es la que de verdad
 * protege: cualquier payload PHP incrustado en los metadatos no sobrevive.
 *
 * El pipeline completo (miniaturas, WebP, cola) llega en la Fase 3; aqui solo
 * hace falta una imagen optimizada por clave.
 */
class SettingsImageService
{
    private const DISK = 'public';

    private const FOLDER = 'settings';

    /** Ancho maximo por tipo de imagen. */
    private const MAX_WIDTH = [
        'site_logo' => 600,
        'site_logo_dark' => 600,
        'site_favicon' => 256,
        'seo_default_og_image' => 1200,
    ];

    public function __construct(
        private SettingsService $settings,
        private ImageManager $images,
    ) {}

    /**
     * Guarda la imagen y devuelve la ruta relativa.
     *
     * Nunca se guardan rutas absolutas: asi el sitio sobrevive a un cambio de
     * dominio o a mover la carpeta del proyecto.
     */
    public function store(string $key, UploadedFile $file): string
    {
        // Nombre aleatorio: evita colisiones, path traversal y filtrar el
        // nombre original del fichero del cliente.
        $name = Str::random(16);
        $isSvg = $file->getMimeType() === 'image/svg+xml';

        if ($isSvg) {
            // Un SVG es texto, no un mapa de bits: Intervention no lo reescribe.
            // Se guarda tal cual y se sirve con Content-Type controlado.
            $path = self::FOLDER."/{$name}.svg";
            Storage::disk(self::DISK)->put($path, $this->sanitizeSvg($file->get()));
        } else {
            // Decodificar y volver a codificar es la capa de seguridad que de
            // verdad cuenta: un payload PHP incrustado en los metadatos no
            // sobrevive al proceso.
            //
            // La regla RealImage ya deberia haber filtrado lo que no es una
            // imagen. Esto es la segunda linea: un archivo corrupto no debe
            // tumbar el panel con un 500.
            try {
                $image = $this->images->read($file->getRealPath());
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    $key => 'No se pudo procesar la imagen. Prueba con otro archivo.',
                ]);
            }

            $maxWidth = self::MAX_WIDTH[$key] ?? 1200;

            if ($image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
            }

            $extension = $key === 'site_favicon' ? 'png' : 'webp';

            $encoded = $key === 'site_favicon'
                ? $image->toPng()
                : $image->toWebp(quality: 88);

            $path = self::FOLDER."/{$name}.{$extension}";
            Storage::disk(self::DISK)->put($path, (string) $encoded);
        }

        $anterior = $this->settings->get($key);

        $this->settings->set($key, $path);

        // El archivo anterior se borra solo si ya no lo usa ninguna otra clave.
        $this->deleteIfUnused($anterior);

        return $path;
    }

    /**
     * Guarda una imagen en una carpeta arbitraria y devuelve su ruta relativa.
     *
     * No toca settings: lo usa quien guarda la ruta en otra tabla, como los
     * bloques de contenido del inicio.
     *
     * @throws ValidationException
     */
    public function storeFor(string $folder, UploadedFile $file, int $maxWidth = 1920): string
    {
        $name = Str::random(16);

        try {
            $image = $this->images->read($file->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'image' => 'No se pudo procesar la imagen. Prueba con otro archivo.',
            ]);
        }

        // Orientar por EXIF y descartarlo, igual que en el resto del sistema.
        $image->orient();

        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $path = "{$folder}/{$name}.webp";

        Storage::disk(self::DISK)->put($path, (string) $image->toWebp(quality: 82));

        return $path;
    }

    public function remove(string $key): void
    {
        $anterior = $this->settings->get($key);

        $this->settings->set($key, null);

        $this->deleteIfUnused($anterior);
    }

    /**
     * Un SVG puede contener <script> y manejadores onload. Se limpian antes de
     * guardarlo porque el navegador lo ejecutaria al mostrarlo en la misma
     * pagina.
     */
    private function sanitizeSvg(string $contents): string
    {
        $contents = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $contents) ?? $contents;
        $contents = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $contents) ?? $contents;

        return preg_replace('#(href|xlink:href)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '', $contents) ?? $contents;
    }

    private function deleteIfUnused(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $claves = array_keys(self::MAX_WIDTH);

        foreach ($claves as $clave) {
            if ($this->settings->get($clave) === $path) {
                return;   // sigue en uso por otra clave
            }
        }

        Storage::disk(self::DISK)->delete($path);
    }
}
