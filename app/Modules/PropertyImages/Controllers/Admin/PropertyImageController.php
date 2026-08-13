<?php

namespace App\Modules\PropertyImages\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyImages\Requests\ImageUploadRequest;
use App\Modules\PropertyImages\Services\PropertyImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints de imagenes. Responden JSON porque el componente de subida es
 * asincrono: la imagen aparece en pantalla sin recargar el formulario.
 */
class PropertyImageController extends Controller
{
    public function __construct(private PropertyImageService $service) {}

    public function store(ImageUploadRequest $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $creadas = [];
        $errores = [];

        foreach ($request->file('images', []) as $archivo) {
            try {
                $imagen = $this->service->add($property, $archivo);
                $creadas[] = $this->present($imagen);
            } catch (ValidationException $e) {
                // Un archivo malo no debe tumbar la subida de los demas:
                // se informa cual fallo y por que, y el resto sigue.
                $errores[] = [
                    'file' => $archivo->getClientOriginalName(),
                    'message' => collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return response()->json([
            'images' => $creadas,
            'errors' => $errores,
        ], $creadas === [] && $errores !== [] ? 422 : 201);
    }

    public function destroy(Property $property, PropertyImage $image): JsonResponse
    {
        $this->authorize('update', $property);
        abort_unless($image->property_id === $property->id, 404);

        $this->service->delete($image);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $datos = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $this->service->reorder($property, $datos['order']);

        return response()->json(['ok' => true]);
    }

    public function setMain(Property $property, PropertyImage $image): JsonResponse
    {
        $this->authorize('update', $property);
        abort_unless($image->property_id === $property->id, 404);

        $this->service->setMain($image);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Property $property, PropertyImage $image): JsonResponse
    {
        $this->authorize('update', $property);
        abort_unless($image->property_id === $property->id, 404);

        $datos = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $image->update($datos);

        return response()->json(['ok' => true, 'image' => $this->present($image->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PropertyImage $image): array
    {
        return [
            'id' => $image->id,
            'thumb' => $image->thumbnailUrl(),
            'url' => $image->url(),
            'is_main' => $image->is_main,
            'alt_text' => $image->alt_text,
            'title' => $image->title,
            'original_name' => $image->original_name,
            'size' => $image->size,
            'width' => $image->width,
            'height' => $image->height,
        ];
    }
}
