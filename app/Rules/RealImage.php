<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Capa 3 de validacion de imagenes (docs/05_MEDIA_UPLOADS.md seccion 2).
 *
 * Las reglas `image` y `mimes` de Laravel se fian del tipo declarado. Esta
 * regla mira el CONTENIDO: si el archivo no es una imagen que se pueda
 * decodificar de verdad, se rechaza aqui, con un mensaje para el usuario,
 * en lugar de reventar mas adelante en el procesamiento.
 *
 * Se detecto en la Fase 1: un .php renombrado a .png llegaba hasta
 * Intervention y provocaba un error 500 en el panel.
 */
class RealImage implements ValidationRule
{
    /** Tipos que acepta el sistema. */
    private const ALLOWED = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
        IMAGETYPE_GIF,
    ];

    /**
     * @param  bool  $allowSvg  si se admiten SVG en este campo
     * @param  int|null  $minSize  lado minimo en pixeles (no aplica a SVG,
     *                             que es vectorial y escala sin perder)
     */
    public function __construct(
        private bool $allowSvg = true,
        private ?int $minSize = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;   // de eso ya se encargan las reglas file/image
        }

        // Se lee a traves del UploadedFile y se valida en memoria.
        // Leer por ruta con file_get_contents falla con los temporales de
        // Windows mientras el manejador sigue abierto.
        try {
            $contents = $value->get();
        } catch (\Throwable) {
            $fail('No se pudo leer el archivo. Vuelve a intentarlo.');

            return;
        }

        if (blank($contents)) {
            $fail('El archivo está vacío.');

            return;
        }

        // Un SVG es texto: getimagesize no sirve. Se comprueba que sea XML con
        // raiz <svg> y que no lleve codigo ejecutable.
        if ($this->looksLikeSvg($contents)) {
            if (! $this->allowSvg) {
                $fail('No se admiten archivos SVG en este campo.');

                return;
            }

            if (preg_match('#<script\b#i', $contents) || preg_match('#\son\w+\s*=#i', $contents)) {
                $fail('El SVG contiene código ejecutable y no se puede usar.');
            }

            return;
        }

        // Cualquier rastro de codigo PHP descalifica el archivo de inmediato.
        if (str_contains($contents, '<?php') || str_contains($contents, '<?=')) {
            $fail('El archivo no es una imagen válida.');

            return;
        }

        $info = @getimagesizefromstring($contents);

        if ($info === false || ! isset($info[2])) {
            $fail('El archivo no es una imagen válida.');

            return;
        }

        if (! in_array($info[2], self::ALLOWED, true)) {
            $fail('El formato de imagen no está admitido.');

            return;
        }

        if ($this->minSize !== null && (($info[0] < $this->minSize) || ($info[1] < $this->minSize))) {
            $fail("La imagen debe medir al menos {$this->minSize}×{$this->minSize} píxeles.");
        }
    }

    private function looksLikeSvg(string $contents): bool
    {
        $inicio = ltrim(substr($contents, 0, 512));

        return str_starts_with($inicio, '<?xml')
            || stripos($inicio, '<svg') !== false;
    }
}
