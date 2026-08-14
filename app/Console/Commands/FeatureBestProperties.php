<?php

namespace App\Console\Commands;

use App\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Marca destacadas y oportunidades de inversion.
 *
 * La portada tiene dos secciones que se alimentan de estas banderas. Si nadie
 * las marca, la portada sale vacia — que es exactamente lo que pasaba tras
 * importar el catalogo: 45 propiedades cargadas y una pagina de inicio sin
 * una sola ficha.
 *
 * ESTO NO SUSTITUYE AL CRITERIO DEL CLIENTE. Destacar una propiedad es una
 * decision comercial: cual conviene empujar esta semana. El comando solo pone
 * un punto de partida razonable —las fichas mejor documentadas, que son las
 * que se ven bien en una tarjeta— para que la portada no arranque en blanco.
 * Se cambia desde el panel en cualquier momento.
 */
class FeatureBestProperties extends Command
{
    protected $signature = 'era:destacar
                            {--featured=6 : Cuantas destacar}
                            {--investment=6 : Cuantas marcar como inversion}
                            {--dry-run : Solo muestra lo que haria}';

    protected $description = 'Marca como destacadas las fichas mejor documentadas y detecta las de inversión';

    /** Palabras que identifican una oportunidad de inversion en el titulo. */
    private const SENALES_INVERSION = [
        'proyecto', 'inversión', 'inversion', 'airbnb', 'rentabilidad',
        'plaza comercial', 'locales comerciales', 'naves industriales',
    ];

    public function handle(): int
    {
        $simulacion = (bool) $this->option('dry-run');

        // --- Destacadas: PRIMERO, y las que mejor se ven en una tarjeta -----
        //
        // Una tarjeta necesita foto y precio para no parecer un hueco. Se
        // ordena por cuantos datos tiene la ficha y, a igualdad, por la mas
        // reciente.
        //
        // Se eligen ANTES que las de inversion a proposito. Al reves, las de
        // inversion se llevaban las fichas mejor documentadas —los proyectos
        // son justo las que traen habitaciones y banos— y en «Destacadas»
        // quedaban las mas pobres. La portada se lee de arriba abajo: la
        // primera seccion es la que tiene que lucir.
        $destacadas = Property::query()
            ->published()
            ->where('status', PropertyStatus::Available)
            ->has('images')
            ->whereNotNull('price')
            ->with(['translations', 'images'])
            ->get()
            ->sortByDesc(fn (Property $p) => [
                $p->bedrooms !== null ? 1 : 0,
                $p->bathrooms !== null ? 1 : 0,
                $p->construction_area !== null ? 1 : 0,
                $p->images->count(),
                $p->published_at?->timestamp ?? 0,
            ])
            ->take((int) $this->option('featured'));

        // --- Inversion: se deduce del contenido, no se elige a dedo ---------
        //
        // Se exige foto por el mismo motivo que en destacadas, y se excluyen
        // las ya destacadas: la portada muestra las dos secciones seguidas y
        // repetir una ficha en ambas se ve como un error.
        $inversion = Property::query()
            ->published()
            ->has('images')
            ->whereNotIn('id', $destacadas->pluck('id'))
            ->with('translations')
            ->get()
            ->filter(fn (Property $p) => Str::contains(
                Str::lower((string) $p->title),
                self::SENALES_INVERSION
            ))
            ->take((int) $this->option('investment'));

        $this->newLine();
        $this->info('Destacadas ('.$destacadas->count().')');
        $this->line(str_repeat('─', 62));

        foreach ($destacadas as $p) {
            $this->line(sprintf(
                '  %-11s %-38s %s',
                $p->reference_code,
                Str::limit((string) $p->title, 36),
                $p->formattedPrice()
            ));
        }

        $this->newLine();
        $this->info('Oportunidades de inversión ('.$inversion->count().')');
        $this->line(str_repeat('─', 62));

        foreach ($inversion as $p) {
            $this->line(sprintf('  %-11s %s', $p->reference_code, Str::limit((string) $p->title, 46)));
        }

        if ($simulacion) {
            $this->newLine();
            $this->comment('  Simulación. Nada se ha marcado.');

            return self::SUCCESS;
        }

        // Se limpian las marcas anteriores para que el comando sea
        // idempotente: ejecutarlo dos veces deja el mismo resultado, no doce
        // destacadas.
        Property::query()->update(['is_featured' => false, 'is_investment' => false]);

        Property::whereIn('id', $destacadas->pluck('id'))->update(['is_featured' => true]);
        Property::whereIn('id', $inversion->pluck('id'))->update(['is_investment' => true]);

        $this->newLine();
        $this->info('Marcadas. Se cambian desde /admin/propiedades cuando haga falta.');

        return self::SUCCESS;
    }
}
