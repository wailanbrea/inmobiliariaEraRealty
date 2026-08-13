<?php

namespace App\Console\Commands;

use App\Modules\Pages\Models\ContentSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Genera un fondo de portada: horizonte caribeno estilizado.
 *
 * IMPORTANTE: NO es una fotografia y no pretende serlo. Es un marcador de
 * posicion generado por codigo (cielo de atardecer, mar turquesa, linea de
 * horizonte) para que la portada no se vea vacia mientras el cliente sube su
 * propia foto desde /admin/contenido.
 *
 * Una inmobiliaria vende con fotos reales de sus propiedades. Este relleno
 * existe solo para que la pagina se vea terminada en desarrollo.
 */
class GenerateHeroPlaceholder extends Command
{
    protected $signature = 'hero:placeholder
                            {--force : Sobrescribe la imagen actual de la portada}';

    protected $description = 'Genera un fondo de portada estilizado, si no hay ninguno';

    private const ANCHO = 1920;

    private const ALTO = 1080;

    /** El horizonte, a poco mas de la mitad: deja aire arriba para el titular. */
    private const HORIZONTE = 0.56;

    public function handle(ImageManager $images): int
    {
        $hero = ContentSection::where('page_key', 'home')
            ->where('section_key', 'hero')
            ->first();

        if (! $hero) {
            $this->error('No existe la sección "hero". Ejecuta primero:');
            $this->line('  php artisan db:seed --class=ContentSectionSeeder');

            return self::FAILURE;
        }

        if ($hero->image && ! $this->option('force')) {
            $this->info('La portada ya tiene imagen. Usa --force para reemplazarla.');

            return self::SUCCESS;
        }

        $this->info('Generando fondo de portada…');

        $imagen = $images->create(self::ANCHO, self::ALTO);

        $horizonte = (int) (self::ALTO * self::HORIZONTE);
        $solX = (int) (self::ANCHO * 0.68);

        // --- Cielo y mar, fila a fila ---
        for ($y = 0; $y < self::ALTO; $y += 2) {
            [$r, $g, $b] = $y < $horizonte
                ? $this->colorCielo($y, $horizonte)
                : $this->colorMar($y, $horizonte);

            $imagen->drawRectangle(0, $y, function ($rect) use ($r, $g, $b) {
                $rect->size(self::ANCHO, 2);
                $rect->background(sprintf('#%02x%02x%02x', $r, $g, $b));
            });
        }

        // --- Resplandor del sol sobre el horizonte ---
        $this->dibujarSol($imagen, $solX, $horizonte);

        // --- Reflejo en el agua ---
        $this->dibujarReflejo($imagen, $solX, $horizonte);

        // --- Oscurecido inferior: el titular va en blanco y necesita contraste ---
        for ($y = (int) (self::ALTO * 0.55); $y < self::ALTO; $y += 2) {
            $intensidad = ($y - self::ALTO * 0.55) / (self::ALTO * 0.45);
            $alfa = min(0.55, $intensidad * 0.55);

            $imagen->drawRectangle(0, $y, function ($rect) use ($alfa) {
                $rect->size(self::ANCHO, 2);
                $rect->background('rgba(19, 27, 46, '.round($alfa, 3).')');
            });
        }

        $ruta = 'content_sections/hero-placeholder.webp';
        Storage::disk('public')->put($ruta, (string) $imagen->toWebp(quality: 82));

        $anterior = $hero->image;
        $hero->update(['image' => $ruta]);
        ContentSection::flushCache('home');

        if ($anterior && $anterior !== $ruta) {
            Storage::disk('public')->delete($anterior);
        }

        $peso = round(Storage::disk('public')->size($ruta) / 1024);

        $this->newLine();
        $this->info("Listo: {$ruta} ({$peso} KB, ".self::ANCHO.'×'.self::ALTO.')');
        $this->newLine();
        $this->warn('  Esto NO es una fotografía: es un fondo generado.');
        $this->line('  Sustitúyelo por una foto real de Cap Cana o de una de tus');
        $this->line('  propiedades desde:  /admin/contenido  →  Portada principal');
        $this->newLine();

        return self::SUCCESS;
    }

    /** Atardecer: azul profundo arriba, calido cerca del horizonte. */
    private function colorCielo(int $y, int $horizonte): array
    {
        $t = $y / $horizonte;               // 0 arriba, 1 en el horizonte
        $t = $t ** 1.6;                     // el calor se concentra abajo

        return [
            (int) round(0x1B + (0xF2 - 0x1B) * $t),
            (int) round(0x3A + (0xC0 - 0x3A) * $t),
            (int) round(0x6B + (0x8A - 0x6B) * $t),
        ];
    }

    /** Mar: turquesa junto al horizonte, azul profundo al acercarse. */
    private function colorMar(int $y, int $horizonte): array
    {
        $t = ($y - $horizonte) / (self::ALTO - $horizonte);

        return [
            (int) round(0x2E + (0x0A - 0x2E) * $t),
            (int) round(0xA8 + (0x3D - 0xA8) * $t),
            (int) round(0xB0 + (0x63 - 0xB0) * $t),
        ];
    }

    private function dibujarSol($imagen, int $centroX, int $horizonte): void
    {
        // Halo, de fuera hacia dentro
        for ($radio = 260; $radio > 0; $radio -= 6) {
            $alfa = (1 - $radio / 260) ** 2 * 0.5;

            $imagen->drawEllipse($centroX, $horizonte - 40, function ($e) use ($radio, $alfa) {
                $e->size($radio * 2, $radio * 2);
                $e->background('rgba(255, 214, 150, '.round($alfa, 3).')');
            });
        }

        // Disco solar
        $imagen->drawEllipse($centroX, $horizonte - 40, function ($e) {
            $e->size(150, 150);
            $e->background('rgba(255, 241, 214, 0.95)');
        });
    }

    private function dibujarReflejo($imagen, int $centroX, int $horizonte): void
    {
        for ($y = $horizonte; $y < self::ALTO; $y += 6) {
            $avance = ($y - $horizonte) / (self::ALTO - $horizonte);

            // El reflejo se ensancha y se apaga al acercarse
            $ancho = (int) (90 + $avance * 420);
            $alfa = (1 - $avance) ** 2 * 0.35;

            if ($alfa < 0.01) {
                continue;
            }

            $imagen->drawRectangle($centroX - (int) ($ancho / 2), $y, function ($rect) use ($ancho, $alfa) {
                $rect->size($ancho, 3);
                $rect->background('rgba(255, 226, 170, '.round($alfa, 3).')');
            });
        }
    }
}
