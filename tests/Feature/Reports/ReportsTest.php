<?php

use App\Enums\LeadSource;
use App\Modules\Leads\Models\Lead;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Reports\Models\PropertyView;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Reports\Services\ViewTracker;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\WhatsApp\Models\WhatsappClick;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    app(SettingsService::class)->flush();

    $this->admin = userWithRole('admin');
});

function propiedadDeReporte(string $titulo = 'Villa en Cap Cana'): Property
{
    $p = Property::factory()->published()->create(['property_type_id' => PropertyType::first()->id]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => $titulo]]);

    return $p->fresh();
}

/*
|--------------------------------------------------------------------------
| Registro de visitas
|--------------------------------------------------------------------------
*/

it('cuenta la visita en el dia correspondiente', function () {
    $p = propiedadDeReporte();

    app(ViewTracker::class)->record($p);

    $fila = PropertyView::firstOrFail();

    expect($fila->property_id)->toBe($p->id)
        ->and($fila->views)->toBe(1)
        ->and($fila->viewed_on->toDateString())->toBe(now()->toDateString());
});

it('acumula sobre la fila del mismo dia en vez de crear otra', function () {
    $p = propiedadDeReporte();

    foreach (range(1, 4) as $i) {
        app(ViewTracker::class)->record($p);
    }

    expect(PropertyView::count())->toBe(1)
        ->and(PropertyView::first()->views)->toBe(4);
});

it('separa los dias', function () {
    $p = propiedadDeReporte();

    app(ViewTracker::class)->record($p, Carbon::now()->subDay());
    app(ViewTracker::class)->record($p);

    expect(PropertyView::count())->toBe(2);
});

it('no guarda ningun dato personal del visitante', function () {
    // La tabla es de estadisticas, no un fichero de datos personales.
    $columnas = Schema::getColumnListing('property_views');

    expect($columnas)->toBe(['id', 'property_id', 'viewed_on', 'views'])
        ->and($columnas)->not->toContain('ip_address')
        ->and($columnas)->not->toContain('user_agent');
});

it('la ficha cuenta la visita una sola vez por sesion', function () {
    // Quien refresca cinco veces no vale lo mismo que cinco interesados.
    $p = propiedadDeReporte('Con visitas');

    $this->get('/propiedades/con-visitas')->assertOk();
    $this->get('/propiedades/con-visitas')->assertOk();
    $this->get('/propiedades/con-visitas')->assertOk();

    expect(PropertyView::first()->views)->toBe(1)
        ->and($p->fresh()->views_count)->toBe(1);
});

it('un borrador no suma visitas', function () {
    $p = Property::factory()->create([
        'property_type_id' => PropertyType::first()->id,
        'published_at' => null,
    ]);
    app(PropertyService::class)->syncTranslations($p, ['es' => ['title' => 'Borrador']]);

    $this->actingAs($this->admin)->get('/propiedades/borrador');

    expect(PropertyView::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Resumen y variación
|--------------------------------------------------------------------------
*/

it('compara contra un periodo anterior de la misma duracion', function () {
    // Comparar 30 dias contra 45 seria enganarse solo.
    $ahora = Carbon::create(2026, 8, 14, 12);
    Carbon::setTestNow($ahora);

    // 10 leads en los ultimos 7 dias, 5 en los 7 anteriores.
    Lead::factory()->count(10)->create(['created_at' => $ahora->copy()->subDays(3)]);
    Lead::factory()->count(5)->create(['created_at' => $ahora->copy()->subDays(10)]);

    $resumen = app(ReportService::class)->summary(
        $ahora->copy()->subDays(6)->startOfDay(),
        $ahora->copy()->endOfDay()
    );

    expect($resumen['leads']['valor'])->toBe(10)
        ->and($resumen['leads']['anterior'])->toBe(5)
        ->and($resumen['leads']['variacion'])->toBe(100.0);

    Carbon::setTestNow();
});

it('la variacion es null cuando no hay con que comparar', function () {
    // Un +100 % al pasar de 0 a 3 es un titular vacio.
    Lead::factory()->count(3)->create();

    $resumen = app(ReportService::class)->summary(now()->subDays(6), now());

    expect($resumen['leads']['valor'])->toBe(3)
        ->and($resumen['leads']['anterior'])->toBe(0)
        ->and($resumen['leads']['variacion'])->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Serie diaria
|--------------------------------------------------------------------------
*/

it('rellena con cero los dias sin datos', function () {
    // Sin ese relleno, una semana en blanco dibujaria una recta entre dos
    // puntos lejanos y pareceria actividad constante donde no la hubo.
    $p = propiedadDeReporte();

    app(ViewTracker::class)->record($p, now()->subDays(5));
    app(ViewTracker::class)->record($p);

    $serie = app(ReportService::class)->dailySeries(now()->subDays(6), now());

    expect($serie)->toHaveCount(7)
        ->and($serie->pluck('visitas')->all())->toBe([0, 1, 0, 0, 0, 0, 1]);
});

it('la serie cubre el rango completo aunque no haya nada', function () {
    $serie = app(ReportService::class)->dailySeries(now()->subDays(29), now());

    expect($serie)->toHaveCount(30)
        ->and($serie->sum('leads'))->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Más vistas
|--------------------------------------------------------------------------
*/

it('ordena por visitas del periodo, no por el total historico', function () {
    // Una ficha de hace dos anos con 900 visitas taparia siempre a la que
    // despierta interes esta semana.
    $vieja = propiedadDeReporte('La veterana');
    $vieja->update(['views_count' => 900]);

    $nueva = propiedadDeReporte('La del momento');
    $nueva->update(['views_count' => 12]);

    app(ViewTracker::class)->record($vieja, now()->subYear());
    foreach (range(1, 12) as $i) {
        app(ViewTracker::class)->record($nueva);
    }

    $ranking = app(ReportService::class)->mostViewed(now()->subDays(29), now());

    expect($ranking)->toHaveCount(1)
        ->and($ranking->first()['property']->id)->toBe($nueva->id)
        ->and($ranking->first()['visitas'])->toBe(12);
});

it('calcula la conversion de visitas a consultas', function () {
    $p = propiedadDeReporte('Con conversion');

    foreach (range(1, 50) as $i) {
        app(ViewTracker::class)->record($p);
    }

    Lead::factory()->count(2)->create([
        'property_id' => $p->id,
        'source' => LeadSource::PropertyDetail,
    ]);

    WhatsappClick::factory()->count(3)->create(['property_id' => $p->id]);

    $fila = app(ReportService::class)->mostViewed(now()->subDays(29), now())->first();

    expect($fila['visitas'])->toBe(50)
        ->and($fila['leads'])->toBe(2)
        ->and($fila['whatsapp'])->toBe(3)
        ->and($fila['conversion'])->toBe(4.0);
});

it('no divide entre cero cuando no hay visitas', function () {
    expect(app(ReportService::class)->mostViewed(now()->subDays(29), now()))->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
*/

it('exige sesion', function () {
    $this->get('/admin/reportes')->assertRedirect(route('admin.login'));
});

it('un administrador entra', function () {
    $this->actingAs($this->admin)->get('/admin/reportes')->assertOk();
});

it('un editor no entra', function () {
    // Los reportes cruzan leads y comportamiento: mismo criterio que la auditoria.
    $this->actingAs(userWithRole('editor'))->get('/admin/reportes')->assertForbidden();
});

it('el dashboard no muestra metricas de negocio a un editor', function () {
    $this->actingAs(userWithRole('editor'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee(__('admin/reports.metrics.leads'), escape: false);
});

it('el dashboard si las muestra a un administrador', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee(__('admin/reports.metrics.leads'), escape: false);
});

/*
|--------------------------------------------------------------------------
| Rango
|--------------------------------------------------------------------------
*/

it('usa los ultimos 30 dias por defecto', function () {
    $this->actingAs($this->admin)
        ->get('/admin/reportes')
        ->assertOk()
        ->assertSee(now()->subDays(29)->format('d/m/Y'))
        ->assertSee(now()->format('d/m/Y'));
});

it('rechaza un rango que termina en el futuro', function () {
    // Solo puede devolver ceros y hace creer que algo se rompio.
    $this->actingAs($this->admin)
        ->get('/admin/reportes?desde='.now()->toDateString().'&hasta='.now()->addMonth()->toDateString())
        ->assertSessionHasErrors('hasta');
});

it('rechaza un hasta anterior al desde', function () {
    $this->actingAs($this->admin)
        ->get('/admin/reportes?desde='.now()->toDateString().'&hasta='.now()->subWeek()->toDateString())
        ->assertSessionHasErrors('hasta');
});

/*
|--------------------------------------------------------------------------
| Exportación
|--------------------------------------------------------------------------
*/

it('exporta un csv con una fila por dia', function () {
    $p = propiedadDeReporte();
    app(ViewTracker::class)->record($p);

    $res = $this->actingAs($this->admin)
        ->get('/admin/reportes/exportar?desde='.now()->subDays(6)->toDateString().'&hasta='.now()->toDateString())
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $lineas = array_filter(explode("\n", $res->streamedContent()));

    // Cabecera + 7 dias.
    expect($lineas)->toHaveCount(8)
        ->and($lineas[0])->toContain('Fecha');
});

it('el csv empieza con el BOM para que Excel respete los acentos', function () {
    // Sin el, Excel en Windows lo abre en ANSI y los acentos salen rotos.
    $contenido = $this->actingAs($this->admin)
        ->get('/admin/reportes/exportar')
        ->streamedContent();

    expect(substr($contenido, 0, 3))->toBe("\xEF\xBB\xBF");
});

it('el nombre del fichero lleva el rango', function () {
    $this->actingAs($this->admin)
        ->get('/admin/reportes/exportar?desde=2026-01-01&hasta=2026-01-31')
        ->assertDownload('era-realty-reporte-2026-01-01_2026-01-31.csv');
});

it('un editor no puede exportar', function () {
    $this->actingAs(userWithRole('editor'))->get('/admin/reportes/exportar')->assertForbidden();
});
