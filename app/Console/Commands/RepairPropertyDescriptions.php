<?php

namespace App\Console\Commands;

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\PropertyTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RepairPropertyDescriptions extends Command
{
    protected $signature = 'properties:repair-descriptions
                            {--dry-run : Mostrar coincidencias sin guardar cambios}
                            {--limit= : Limitar la cantidad de fichas de origen}';

    protected $description = 'Completa descripciones desde el sitio original sin modificar otros campos';

    public function handle(): int
    {
        $source = $this->downloadSource();
        $properties = Property::withTrashed()->with('translations')->get();
        $matched = 0;
        $ambiguous = 0;
        $unmatched = 0;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        foreach ($source as $item) {
            if ($limit !== null && $matched >= $limit) {
                break;
            }

            $description = $this->descriptionFromContent($item['content']['rendered'] ?? '');
            if ($description === '') {
                $this->warn('Sin descripcion: '.($item['title']['rendered'] ?? $item['id']));
                continue;
            }

            $candidates = $this->findCandidates($properties, $item);
            if (count($candidates) !== 1) {
                count($candidates) > 1 ? $ambiguous++ : $unmatched++;
                $this->line(($candidates ? 'AMBIGUA ' : 'NO ENCONTRADA ').
                    ($this->referenceFromContent($item['content']['rendered'] ?? '') ?: 'SIN-REF').' '.
                    trim(html_entity_decode($item['title']['rendered'] ?? (string) $item['id'])));
                continue;
            }

            $property = $candidates[0];
            $translation = $property->translations->firstWhere('locale', 'es');
            if (! $translation) {
                $this->warn('Sin traduccion ES: '.$property->reference_code);
                continue;
            }

            $matched++;
            $this->line($property->reference_code.' '.Str::limit($property->title, 55));

            if (! $this->option('dry-run')) {
                PropertyTranslation::whereKey($translation->id)->update([
                    'description' => $description,
                ]);
            }
        }

        $this->newLine();
        $this->info(($this->option('dry-run') ? 'Coincidencias: ' : 'Actualizadas: ').$matched);
        $this->comment('Ambiguas: '.$ambiguous.' | No encontradas: '.$unmatched);

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function downloadSource(): array
    {
        $all = [];

        for ($page = 1; $page <= 10; $page++) {
            $response = Http::timeout(60)
                ->retry(3, 1500, throw: false)
                ->withHeaders(['User-Agent' => 'ERA Realty RD description repair'])
                ->get('https://erarealtyrd.com/wp-json/wp/v2/properties', [
                    'per_page' => 100,
                    'page' => $page,
                    'status' => 'publish',
                ]);

            if ($response->failed() || ! is_array($response->json()) || $response->json() === []) {
                break;
            }

            $all = array_merge($all, $response->json());
            $this->line('Origen pagina '.$page.': '.count($response->json()).' fichas');

            if (count($response->json()) < 100) {
                break;
            }
        }

        return $all;
    }

    private function descriptionFromContent(string $html): string
    {
        $text = html_entity_decode(
            strip_tags(str_replace(['<br />', '<br>', '</p>'], ["\n", "\n", "\n\n"], $html)),
            ENT_QUOTES | ENT_HTML5,
        );

        $lines = preg_split('/\R/u', $text) ?: [];
        $lines = array_map(fn ($line) => trim(preg_replace('/[ \t]+/u', ' ', $line)), $lines);
        $lines = array_values(array_filter($lines, 'strlen'));

        // El encabezado vive en columnas propias del nuevo modelo. Se omite
        // solo ese bloque inicial y se conserva todo el cuerpo restante.
        while ($lines !== [] && $this->isSourceHeaderLine($lines[0])) {
            array_shift($lines);
        }

        return trim(implode("\n", $lines));
    }

    private function isSourceHeaderLine(string $line): bool
    {
        return str_contains($line, '📍')
            || preg_match('/^(Ref\.?|Referencia|Numero de Referencia|Inversion|Renta|Precio|VENDIDO|RENTADO)/iu', $line) === 1;
    }

    /** @return list<Property> */
    private function findCandidates($properties, array $item): array
    {
        $title = trim(html_entity_decode($item['title']['rendered'] ?? '', ENT_QUOTES | ENT_HTML5));
        $reference = $this->referenceFromContent($item['content']['rendered'] ?? '');
        $titleKey = $this->key($title);

        $exact = collect();
        if ($reference !== '') {
            $exact = $properties->filter(fn (Property $property) => $property->reference_code === $reference);
            $exactTitle = $exact->filter(fn (Property $property) => $this->key($property->title) === $titleKey);
            if ($exactTitle->count() === 1) {
                return $exactTitle->values()->all();
            }
        }

        $byTitle = $properties->filter(fn (Property $property) => $this->key($property->title) === $titleKey);
        if ($byTitle->count() === 1) {
            return $byTitle->values()->all();
        }

        $relatedTitle = $properties->filter(function (Property $property) use ($titleKey) {
            $propertyKey = $this->key($property->title);

            return $propertyKey !== ''
                && (str_contains($propertyKey, $titleKey) || str_contains($titleKey, $propertyKey));
        });

        if ($relatedTitle->count() === 1) {
            return $relatedTitle->values()->all();
        }

        if ($exact->count() === 1) {
            return $exact->values()->all();
        }

        return [];
    }

    private function referenceFromContent(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br />', '<br>'], "\n", $html)), ENT_QUOTES | ENT_HTML5);

        if (preg_match('/(?:Ref\.?|Numero de Referencia|Número de Referencia)\s*:?[\s]*([A-Z0-9-]{3,30})/iu', $text, $match)) {
            return strtoupper($match[1]);
        }

        return '';
    }

    private function key(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-')->value();
    }
}
