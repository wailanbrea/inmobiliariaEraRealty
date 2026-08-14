<?php

namespace App\Modules\Seo\Services;

use App\Enums\PropertyStatus;
use App\Modules\News\Models\NewsPost;
use App\Modules\Properties\Models\Property;
use App\Support\Locale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Construye el sitemap del sitio publico.
 *
 * Decision central: el sitio es bilingue, asi que cada URL declara sus
 * alternativas con xhtml:link. Un sitemap que solo listara las URL en espanol
 * dejaria la mitad del sitio sin indexar, y uno que listara ambas SIN
 * declararlas como alternativas haria que Google las tratara como paginas
 * distintas compitiendo entre si por la misma consulta.
 *
 * Ver docs/15_I18N.md seccion 6.
 */
class SitemapService
{
    /**
     * Paginas fijas y su prioridad.
     *
     * 'privacy' y 'terms' quedan fuera a proposito: son paginas de tramite,
     * no de captacion, y solo diluyen el presupuesto de rastreo.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private const STATIC_PAGES = [
        'home' => ['priority' => '1.0', 'changefreq' => 'daily'],
        'properties.index' => ['priority' => '0.9', 'changefreq' => 'daily'],
        'invest.index' => ['priority' => '0.8', 'changefreq' => 'weekly'],
        'about.index' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'news.index' => ['priority' => '0.7', 'changefreq' => 'weekly'],
        'contact.index' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'publish.index' => ['priority' => '0.7', 'changefreq' => 'monthly'],
    ];

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, alternates: array<string,string>}>
     */
    public function entries(): Collection
    {
        return collect()
            ->concat($this->staticPages())
            ->concat($this->properties())
            ->concat($this->news());
    }

    /**
     * Cada pagina fija existe en los dos idiomas y ambas se listan, cada una
     * declarando a la otra como alternativa.
     */
    private function staticPages(): Collection
    {
        $entradas = collect();

        foreach (self::STATIC_PAGES as $ruta => $ajustes) {
            $alternativas = [];

            foreach (Locale::codes() as $code) {
                $nombre = "{$code}.{$ruta}";

                if (Route::has($nombre)) {
                    $alternativas[$code] = route($nombre);
                }
            }

            foreach ($alternativas as $url) {
                $entradas->push([
                    'loc' => $url,
                    'lastmod' => null,
                    'changefreq' => $ajustes['changefreq'],
                    'priority' => $ajustes['priority'],
                    'alternates' => $alternativas,
                ]);
            }
        }

        return $entradas;
    }

    /**
     * Solo entran las propiedades indexables.
     *
     * Vendido y alquilado siguen siendo visibles en el sitio —son prueba
     * social— pero no van al sitemap: no se puede pedirle a Google que
     * posicione una pagina que va a decepcionar a quien la abra.
     */
    private function properties(): Collection
    {
        return Property::query()
            ->published()
            ->whereIn('status', array_filter(
                PropertyStatus::cases(),
                fn (PropertyStatus $s) => $s->isIndexable()
            ))
            ->with('translations')
            ->get()
            ->flatMap(fn (Property $property) => $this->localizedEntries(
                $property,
                'properties.show',
                $property->updated_at,
                '0.8',
                'weekly'
            ));
    }

    private function news(): Collection
    {
        return NewsPost::query()
            ->published()
            ->with('translations')
            ->get()
            ->flatMap(fn (NewsPost $post) => $this->localizedEntries(
                $post,
                'news.show',
                $post->updated_at,
                '0.6',
                'monthly'
            ));
    }

    /**
     * Genera una entrada por idioma en el que el modelo tenga slug.
     *
     * Un modelo sin traducir al ingles aparece SOLO en espanol, y sin declarar
     * un alternate que no existe: apuntar a un 404 desde el sitemap es peor
     * que no apuntar a nada.
     */
    private function localizedEntries(
        object $model,
        string $ruta,
        ?Carbon $lastmod,
        string $priority,
        string $changefreq
    ): array {
        $alternativas = [];

        foreach (Locale::codes() as $code) {
            $slug = $model->translatedSlug($code);
            $nombre = "{$code}.{$ruta}";

            if ($slug !== null && Route::has($nombre)) {
                $alternativas[$code] = route($nombre, ['slug' => $slug]);
            }
        }

        return collect($alternativas)->map(fn (string $url) => [
            'loc' => $url,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
            'alternates' => $alternativas,
        ])->values()->all();
    }
}
