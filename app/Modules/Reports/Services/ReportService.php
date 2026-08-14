<?php

namespace App\Modules\Reports\Services;

use App\Enums\LeadStatus;
use App\Enums\PropertyStatus;
use App\Modules\Leads\Models\Lead;
use App\Modules\News\Models\NewsPost;
use App\Modules\Properties\Models\Property;
use App\Modules\Reports\Models\PropertyView;
use App\Modules\WhatsApp\Models\WhatsappClick;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consultas de los reportes y del dashboard.
 *
 * Todo metodo acepta un rango de fechas. Un numero sin periodo no dice nada:
 * «120 leads» puede ser un exito o una caida segun sean de un mes o de un ano.
 */
class ReportService
{
    /**
     * Resumen de cabecera del dashboard.
     *
     * Cada metrica lleva su variacion respecto al periodo ANTERIOR de la misma
     * duracion. Es lo que convierte un dato en una noticia: 40 leads no dicen
     * nada; 40 leads cuando el mes pasado hubo 25, si.
     *
     * @return array<string, array{valor: int, anterior: int, variacion: ?float}>
     */
    public function summary(Carbon $desde, Carbon $hasta): array
    {
        // El periodo anterior tiene exactamente la misma duracion, pegado al
        // inicio del actual: comparar 30 dias contra 45 seria enganarse solo.
        $dias = max(1, $desde->diffInDays($hasta) + 1);
        $desdePrevio = (clone $desde)->subDays($dias);
        $hastaPrevio = (clone $desde)->subDay();

        $comparar = fn (callable $consulta) => $this->conVariacion(
            $consulta($desde, $hasta),
            $consulta($desdePrevio, $hastaPrevio),
        );

        return [
            'leads' => $comparar(fn ($d, $h) => Lead::whereBetween('created_at', [$d->startOfDay(), $h->endOfDay()])->count()),
            'whatsapp' => $comparar(fn ($d, $h) => WhatsappClick::whereBetween('created_at', [$d->startOfDay(), $h->endOfDay()])->count()),
            'visitas' => $comparar(fn ($d, $h) => (int) PropertyView::whereBetween('viewed_on', [$d->toDateString(), $h->toDateString()])->sum('views')),
            'publicadas' => $comparar(fn ($d, $h) => Property::published()->whereBetween('published_at', [$d->startOfDay(), $h->endOfDay()])->count()),
        ];
    }

    /**
     * @return array{valor: int, anterior: int, variacion: ?float}
     */
    private function conVariacion(int $actual, int $anterior): array
    {
        return [
            'valor' => $actual,
            'anterior' => $anterior,
            // Sin base de comparacion no hay porcentaje. Devolver 100 % cuando
            // se pasa de 0 a 3 seria un titular vacio: null deja que la vista
            // muestre «sin datos previos», que es la verdad.
            'variacion' => $anterior > 0 ? round((($actual - $anterior) / $anterior) * 100, 1) : null,
        ];
    }

    /**
     * Totales que no dependen del rango: el estado actual del inventario.
     *
     * @return array<string, int>
     */
    public function inventory(): array
    {
        $porEstado = Property::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => Property::count(),
            'publicadas' => Property::published()->count(),
            'borradores' => Property::whereNull('published_at')->count(),
            'disponibles' => (int) ($porEstado[PropertyStatus::Available->value] ?? 0),
            'vendidas' => (int) ($porEstado[PropertyStatus::Sold->value] ?? 0),
            'sin_fotos' => Property::doesntHave('images')->count(),
            'noticias' => NewsPost::count(),
            'leads_nuevos' => Lead::where('status', LeadStatus::New)->count(),
        ];
    }

    /**
     * Serie diaria para el grafico. Rellena los dias sin datos con cero.
     *
     * Sin ese relleno, una semana sin leads dibujaria una linea recta entre
     * dos puntos lejanos y pareceria actividad constante donde no la hubo.
     *
     * @return Collection<int, array{fecha: string, leads: int, whatsapp: int, visitas: int, publicadas: int}>
     */
    public function dailySeries(Carbon $desde, Carbon $hasta): Collection
    {
        $leads = $this->porDia(Lead::query(), 'created_at', $desde, $hasta);
        $clics = $this->porDia(WhatsappClick::query(), 'created_at', $desde, $hasta);

        // Cada tarjeta dibuja SU propia serie. Reutilizar la de leads bajo el
        // rotulo «propiedades publicadas» seria un grafico que miente, que es
        // peor que no dibujar ninguno.
        $publicadas = $this->porDia(Property::published()->getQuery(), 'published_at', $desde, $hasta);

        $visitas = PropertyView::query()
            ->whereBetween('viewed_on', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('viewed_on as dia, SUM(views) as total')
            ->groupBy('viewed_on')
            ->pluck('total', 'dia');

        $serie = collect();
        $cursor = (clone $desde)->startOfDay();

        while ($cursor->lte($hasta)) {
            $dia = $cursor->toDateString();

            $serie->push([
                'fecha' => $dia,
                'leads' => (int) ($leads[$dia] ?? 0),
                'whatsapp' => (int) ($clics[$dia] ?? 0),
                'visitas' => (int) ($visitas[$dia] ?? 0),
                'publicadas' => (int) ($publicadas[$dia] ?? 0),
            ]);

            $cursor->addDay();
        }

        return $serie;
    }

    /**
     * Agrupa por dia sin depender del dialecto: DATE() existe en MySQL y en
     * SQLite, que es el motor de las pruebas.
     */
    private function porDia($query, string $columna, Carbon $desde, Carbon $hasta): Collection
    {
        return $query
            ->whereBetween($columna, [(clone $desde)->startOfDay(), (clone $hasta)->endOfDay()])
            ->selectRaw("DATE({$columna}) as dia, COUNT(*) as total")
            ->groupBy('dia')
            ->pluck('total', 'dia');
    }

    /**
     * Propiedades mas vistas en el rango.
     *
     * Se lee de property_views y no de 'views_count', que es el total
     * historico: una ficha de hace dos anos con 900 visitas taparia siempre a
     * la que despierta interes esta semana, que es justo lo que el informe
     * tiene que sacar a la luz.
     */
    public function mostViewed(Carbon $desde, Carbon $hasta, int $limite = 10): Collection
    {
        $totales = PropertyView::query()
            ->whereBetween('viewed_on', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('property_id, SUM(views) as total')
            ->groupBy('property_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('total', 'property_id');

        if ($totales->isEmpty()) {
            return collect();
        }

        $clics = WhatsappClick::query()
            ->whereIn('property_id', $totales->keys())
            ->whereBetween('created_at', [(clone $desde)->startOfDay(), (clone $hasta)->endOfDay()])
            ->selectRaw('property_id, COUNT(*) as total')
            ->groupBy('property_id')
            ->pluck('total', 'property_id');

        $leads = Lead::query()
            ->whereIn('property_id', $totales->keys())
            ->whereBetween('created_at', [(clone $desde)->startOfDay(), (clone $hasta)->endOfDay()])
            ->selectRaw('property_id, COUNT(*) as total')
            ->groupBy('property_id')
            ->pluck('total', 'property_id');

        return Property::query()
            ->whereIn('id', $totales->keys())
            ->with('translations')
            ->get()
            ->map(function (Property $p) use ($totales, $clics, $leads) {
                $visitas = (int) $totales[$p->id];
                $consultas = (int) ($leads[$p->id] ?? 0);

                return [
                    'property' => $p,
                    'visitas' => $visitas,
                    'whatsapp' => (int) ($clics[$p->id] ?? 0),
                    'leads' => $consultas,
                    // El dato que de verdad importa: cuantas visitas hacen
                    // falta para que alguien escriba. Una ficha muy vista y
                    // sin consultas tiene un problema — precio, fotos o texto.
                    'conversion' => $visitas > 0 ? round(($consultas / $visitas) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('visitas')
            ->values();
    }

    /**
     * Reparto de leads por origen y por estado.
     *
     * @return array{origen: Collection, estado: Collection, total: int}
     */
    public function leadBreakdown(Carbon $desde, Carbon $hasta): array
    {
        $base = fn () => Lead::whereBetween('created_at', [(clone $desde)->startOfDay(), (clone $hasta)->endOfDay()]);

        return [
            'origen' => $base()->selectRaw('source, COUNT(*) as total')->groupBy('source')->pluck('total', 'source'),
            'estado' => $base()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'total' => $base()->count(),
        ];
    }

    /**
     * Clics de WhatsApp por origen.
     */
    public function whatsappBreakdown(Carbon $desde, Carbon $hasta): Collection
    {
        return WhatsappClick::query()
            ->whereBetween('created_at', [(clone $desde)->startOfDay(), (clone $hasta)->endOfDay()])
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source');
    }

    /**
     * Filas planas para la exportacion CSV.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(Carbon $desde, Carbon $hasta): Collection
    {
        return $this->dailySeries($desde, $hasta)->map(fn (array $d) => [
            'fecha' => $d['fecha'],
            'visitas' => $d['visitas'],
            'clics_whatsapp' => $d['whatsapp'],
            'leads' => $d['leads'],
        ]);
    }

    /**
     * Rango por defecto: los ultimos 30 dias, hoy incluido.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function defaultRange(): array
    {
        return [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()];
    }
}
