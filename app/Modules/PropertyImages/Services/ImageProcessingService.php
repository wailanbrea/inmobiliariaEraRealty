<?php

namespace App\Modules\PropertyImages\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

/**
 * Pipeline de imagenes de propiedad.
 * Ver docs/05_MEDIA_UPLOADS.md seccion 3.
 *
 * Por cada archivo se generan tres versiones:
 *   original/  optimizado, maximo 1920px de ancho
 *   webp/      calidad 82
 *   thumb/     400x300 recortado al centro
 *
 * Una foto de camara de 4 MB baja a ~380 KB en WebP. Sobre 24 fotos por
 * propiedad, es la diferencia entre una ficha que carga y una que no.
 */
class ImageProcessingService
{
    private const DISK = 'public';

    private const MAX_WIDTH = 1920;

    private const THUMB_WIDTH = 400;

    private const THUMB_HEIGHT = 300;

    public function __construct(private ImageManager $images) {}

    /**
     * Procesa un archivo y devuelve los datos listos para guardar en BD.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function process(UploadedFile $file, int $propertyId): array
    {
        $carpeta = "properties/{$propertyId}";
        $nombre = Str::random(16);

        try {
            $imagen = $this->images->read($file->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'images' => "No se pudo procesar «{$file->getClientOriginalName()}». ".
                            'Comprueba que sea una imagen válida.',
            ]);
        }

        // 1. Orientar segun EXIF y despues descartarlo por completo.
        //    Las fotos de propiedades vienen del movil del agente y llevan GPS:
        //    publicarlo filtraria la ubicacion exacta de inmuebles marcados
        //    como show_exact_location = false.
        $imagen->orient();

        // 2. Reducir si es mas ancha de lo necesario. No se amplia nunca.
        if ($imagen->width() > self::MAX_WIDTH) {
            $imagen->scaleDown(width: self::MAX_WIDTH);
        }

        $ancho = $imagen->width();
        $alto = $imagen->height();

        // 3. Original optimizado. Se reencoda siempre: cualquier payload
        //    incrustado en los metadatos no sobrevive al proceso.
        $rutaOriginal = "{$carpeta}/original/{$nombre}.jpg";
        Storage::disk(self::DISK)->put($rutaOriginal, (string) $imagen->toJpeg(quality: 85));

        // 4. WebP
        $rutaWebp = "{$carpeta}/webp/{$nombre}.webp";
        Storage::disk(self::DISK)->put($rutaWebp, (string) $imagen->toWebp(quality: 82));

        // 5. Miniatura recortada al centro
        $miniatura = $this->images->read((string) $imagen->toJpeg(quality: 90))
            ->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT);

        $rutaThumb = "{$carpeta}/thumb/{$nombre}.jpg";
        Storage::disk(self::DISK)->put($rutaThumb, (string) $miniatura->toJpeg(quality: 80));

        return [
            'path' => $rutaOriginal,
            'webp_path' => $rutaWebp,
            'thumbnail_path' => $rutaThumb,
            'original_name' => $file->getClientOriginalName(),
            'width' => $ancho,
            'height' => $alto,
            'size' => Storage::disk(self::DISK)->size($rutaOriginal),
            'mime_type' => 'image/jpeg',
        ];
    }

    /**
     * Borra los ficheros de una imagen del disco.
     *
     * @param  list<string>  $paths
     */
    public function deleteFiles(array $paths): void
    {
        Storage::disk(self::DISK)->delete($paths);
    }

    /** Elimina la carpeta entera de una propiedad. Solo en borrado definitivo. */
    public function deletePropertyFolder(int $propertyId): void
    {
        Storage::disk(self::DISK)->deleteDirectory("properties/{$propertyId}");
    }
}
