<?php

namespace App\Modules\PropertyImages\Services;

use App\Modules\Properties\Models\Property;
use App\Modules\PropertyImages\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orden, imagen principal y ciclo de vida de las imagenes de una propiedad.
 *
 * Invariante que sostiene todo lo demas: cada propiedad con imagenes tiene
 * EXACTAMENTE una marcada como principal. No se puede garantizar con un indice
 * unico (MariaDB no tiene UNIQUE parcial), asi que se garantiza aqui, dentro
 * de transaccion.
 */
class PropertyImageService
{
    /** Limite del prompt maestro (§9). */
    public const MAX_IMAGES = 30;

    public function __construct(private ImageProcessingService $processor) {}

    /**
     * Sube un archivo y lo asocia a la propiedad.
     *
     * @throws ValidationException
     */
    public function add(Property $property, UploadedFile $file): PropertyImage
    {
        $actuales = $property->images()->count();

        if ($actuales >= self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'Esta propiedad ya tiene el máximo de '.self::MAX_IMAGES.' imágenes.',
            ]);
        }

        $datos = $this->processor->process($file, $property->id);

        return DB::transaction(function () use ($property, $datos, $actuales) {
            $imagen = $property->images()->create($datos + [
                'sort_order' => ($property->images()->max('sort_order') ?? -1) + 1,
                // La primera imagen que se sube es la principal por defecto:
                // si no, la ficha saldria sin foto de portada.
                'is_main' => $actuales === 0,
                'uploaded_by_user_id' => auth()->id(),
            ]);

            return $imagen;
        });
    }

    /**
     * Marca una imagen como principal y desmarca la anterior en la misma
     * transaccion, para que nunca haya dos ni ninguna.
     */
    public function setMain(PropertyImage $image): void
    {
        DB::transaction(function () use ($image) {
            PropertyImage::where('property_id', $image->property_id)
                ->where('id', '!=', $image->id)
                ->update(['is_main' => false]);

            $image->update(['is_main' => true]);
        });
    }

    /**
     * Reordena segun la lista de ids recibida.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(Property $property, array $orderedIds): void
    {
        DB::transaction(function () use ($property, $orderedIds) {
            foreach ($orderedIds as $posicion => $id) {
                PropertyImage::where('property_id', $property->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $posicion]);
            }
        });
    }

    /**
     * Borra la imagen, sus ficheros, y promueve otra a principal si hacia
     * falta. Sin esto, borrar la portada dejaria la ficha sin foto.
     */
    public function delete(PropertyImage $image): void
    {
        $rutas = $image->allPaths();
        $eraPrincipal = $image->is_main;
        $propertyId = $image->property_id;

        DB::transaction(function () use ($image, $eraPrincipal, $propertyId) {
            $image->delete();

            if ($eraPrincipal) {
                $siguiente = PropertyImage::where('property_id', $propertyId)
                    ->orderBy('sort_order')
                    ->first();

                $siguiente?->update(['is_main' => true]);
            }
        });

        // Los ficheros se borran despues de confirmar la transaccion: si algo
        // falla en BD, no se pierden los archivos.
        $this->processor->deleteFiles($rutas);
    }

    /**
     * Repara la invariante si por lo que sea se rompio: deja exactamente una
     * principal. Lo usa el comando de mantenimiento.
     */
    public function ensureSingleMain(Property $property): void
    {
        $imagenes = $property->images()->orderBy('sort_order')->get();

        if ($imagenes->isEmpty()) {
            return;
        }

        $principales = $imagenes->where('is_main', true);

        if ($principales->count() === 1) {
            return;
        }

        DB::transaction(function () use ($imagenes, $principales) {
            $elegidaId = ($principales->first() ?? $imagenes->first())->id;

            PropertyImage::whereIn('id', $imagenes->pluck('id'))
                ->update(['is_main' => false]);

            // Por consulta, no por el modelo: la instancia en memoria todavia
            // cree que is_main es true, asi que un ->update() no la veria
            // sucia y no escribiria nada.
            PropertyImage::whereKey($elegidaId)->update(['is_main' => true]);
        });
    }
}
