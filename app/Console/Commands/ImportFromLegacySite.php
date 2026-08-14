<?php

namespace App\Console\Commands;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Importa propiedades del sitio anterior (WordPress) al nuevo.
 *
 * POR QUE NO ES UN SEEDER: esto habla con una web ajena, descarga ficheros y
 * puede tardar minutos. Un seeder se ejecuta sin pensar, y `db:seed --fresh`
 * en produccion dispararia 122 descargas contra el servidor del cliente.
 *
 * LO QUE SE IMPORTA Y LO QUE NO
 * -----------------------------
 * El sitio viejo guarda los datos en TEXTO LIBRE, no en campos: la ficha tiene
 * una tabla de detalles («No. Habitaciones», «Baños»…) que esta vacia en todas
 * las propiedades. Solo son fiables las tres primeras lineas del contenido,
 * que siguen un patron constante:
 *
 *     📍 Juan Dolio
 *     Ref. 735-V
 *     Inversión US$380,000
 *
 * Se importa lo que se puede leer con certeza —titulo, descripcion,
 * referencia, ubicacion, precio, moneda, operacion y tipo— y se deja en blanco
 * lo demas. Inventar tres habitaciones porque «suena a villa» seria peor que
 * dejarlo vacio: el panel marca los huecos y el cliente los rellena.
 *
 * El tipo sale del SUFIJO de la referencia, que es el dato mas fiable de todos:
 * 735-V es villa, 733-A apartamento, 727-S solar, 726-L local.
 */
class ImportFromLegacySite extends Command
{
    protected $signature = 'era:import
                            {--limit=20 : Cuantas propiedades importar}
                            {--images=8 : Maximo de fotos por propiedad}
                            {--dry-run : Solo muestra lo que haria}
                            {--force : Reimporta las que ya existan}';

    protected $description = 'Importa propiedades desde erarealtyrd.com (sitio anterior en WordPress)';

    private const ORIGEN = 'https://erarealtyrd.com';

    /** Sufijo de la referencia -> slug del tipo en el catalogo nuevo. */
    private const TIPOS = [
        'V' => 'villa',
        'A' => 'apartamento',
        'C' => 'casa',
        'S' => 'solar',
        'T' => 'terreno',
        'L' => 'local-comercial',
        'O' => 'oficina',
        'P' => 'penthouse',
        'F' => 'finca',
        'N' => 'nave',
    ];

    private ImageManager $imagenes;

    public function handle(ImageManager $imagenes, PropertyService $servicio): int
    {
        $this->imagenes = $imagenes;

        $limite = (int) $this->option('limit');
        $simulacion = (bool) $this->option('dry-run');

        $this->info('Importando desde '.self::ORIGEN);
        $this->line(str_repeat('─', 60));

        $fichas = $this->descargarListado($limite);

        if ($fichas === []) {
            $this->error('No se pudo leer el catálogo del sitio anterior.');

            return self::FAILURE;
        }

        $this->line('  Candidatas leídas: '.count($fichas));
        $this->newLine();

        $importadas = 0;
        $saltadas = 0;

        /** Referencias que chocaron y hubo que renombrar. */
        $duplicadas = [];

        /** Fichas cuyo precio venia por metro y quedan sin precio. */
        $sinPrecio = [];

        foreach ($fichas as $ficha) {
            if ($importadas >= $limite) {
                break;
            }

            $datos = $this->interpretar($ficha);

            if ($datos === null) {
                $saltadas++;

                continue;
            }

            // REFERENCIAS REPETIDAS EN ORIGEN.
            //
            // El sitio viejo tiene la misma referencia en fichas distintas
            // —719-A es «Proyecto de Villas en Vista Cana» y tambien «Proyecto
            // Apartamentos Juan Dolio»—, pero aqui reference_code es UNICO.
            // Sin esto, la segunda revienta contra el indice, o con --force
            // machaca a la primera y se pierde una propiedad en silencio.
            //
            // Se importan las dos, y a la repetida se le anade el id de
            // WordPress: queda visible que hay un choque y de donde salio,
            // en vez de decidir por el cliente cual de las dos sobra.
            $referencia = $datos['reference_code'];
            $existente = Property::where('reference_code', $referencia)->first();

            $mismaFicha = $existente
                && $existente->title === $datos['title'];

            if ($existente && ! $mismaFicha) {
                $referencia = $datos['reference_code'].'-'.$datos['wp_id'];
                $duplicadas[] = $datos['reference_code'].' → '.$referencia.'  ('.$datos['title'].')';
                $existente = Property::where('reference_code', $referencia)->first();
            }

            $datos['reference_code'] = $referencia;

            if ($existente && ! $this->option('force')) {
                $this->line("  <fg=gray>ya existe</>  {$referencia}  {$datos['title']}");
                $saltadas++;

                continue;
            }

            if ($datos['por_metro']) {
                $sinPrecio[] = $datos['reference_code'].'  '.Str::limit($datos['title'], 40).
                    '  ('.$datos['por_metro'].')';
            }

            if ($simulacion) {
                $this->line("  <fg=cyan>importaría</> {$datos['reference_code']}  ".Str::limit($datos['title'], 45));
                $this->line("               {$datos['tipo']} · {$datos['ubicacion']} · ".
                    ($datos['price'] ? $datos['currency']->value.' '.number_format($datos['price']) : 'a consultar'));
                $importadas++;

                continue;
            }

            $property = $this->crear($datos, $servicio, $existente);
            $fotos = $this->importarImagenes($property, $ficha['link']);

            $this->line("  <fg=green>importada</>  {$datos['reference_code']}  ".
                Str::limit($datos['title'], 40)." <fg=gray>({$fotos} fotos)</>");

            $importadas++;

            // Un respiro entre fichas: son peticiones a un servidor ajeno y no
            // hay ninguna prisa por terminar medio segundo antes.
            usleep(400_000);
        }

        $this->newLine();
        $this->info($simulacion
            ? "Simulación: se importarían {$importadas} propiedades ({$saltadas} saltadas)."
            : "Importadas {$importadas} propiedades ({$saltadas} saltadas).");

        // Lo que necesita una decision humana se enumera al final, en vez de
        // quedar enterrado entre cien lineas de progreso.
        if ($duplicadas !== []) {
            $this->newLine();
            $this->warn('Referencias repetidas en el sitio anterior ('.count($duplicadas).'):');
            foreach ($duplicadas as $d) {
                $this->line('    '.$d);
            }
            $this->comment('  Se importaron las dos. Revisa cuál conserva la referencia buena.');
        }

        if ($sinPrecio !== []) {
            $this->newLine();
            $this->warn('Sin precio, porque el sitio anterior lo da por m² ('.count($sinPrecio).'):');
            foreach ($sinPrecio as $s) {
                $this->line('    '.$s);
            }
            $this->comment('  Salen como «precio a consultar». El dato literal está en la descripción.');
        }

        if ($simulacion) {
            $this->newLine();
            $this->comment('  Para ejecutarlo de verdad, quita --dry-run.');
        }

        return self::SUCCESS;
    }

    /**
     * Lee el catalogo por la API REST de WordPress.
     *
     * @return list<array<string, mixed>>
     */
    private function descargarListado(int $limite): array
    {
        $todas = [];
        $pagina = 1;

        // Se piden mas de las que se necesitan porque muchas se descartan:
        // vendidas, rentadas o sin el patron de referencia.
        while (count($todas) < $limite * 4 && $pagina <= 5) {
            try {
                // Reintentos con espera: el servidor de origen corta la
                // conexion cuando se le piden varias paginas seguidas.
                $respuesta = Http::timeout(45)
                    ->retry(3, 2000, throw: false)
                    ->withHeaders(['User-Agent' => 'ERA Realty RD importer'])
                    ->get(self::ORIGEN.'/wp-json/wp/v2/properties', [
                        'per_page' => 50,
                        'page' => $pagina,
                        'status' => 'publish',
                    ]);
            } catch (\Throwable $e) {
                // UNA pagina caida no puede tumbar la importacion entera.
                //
                // Paso: el catalogo tiene 122 fichas y el servidor reseteo la
                // conexion en la pagina 3. Sin esto, la excepcion subia hasta
                // el comando y se perdian las 100 fichas ya descargadas —y el
                // trabajo de las que ya se habian guardado.
                $this->warn('  Página '.$pagina.' no respondió ('.Str::limit($e->getMessage(), 50).').');
                $this->comment('  Se continúa con las '.count($todas).' fichas ya leídas.');
                break;
            }

            if ($respuesta->failed()) {
                break;
            }

            $lote = $respuesta->json();

            if (! is_array($lote) || $lote === []) {
                break;
            }

            $todas = array_merge($todas, $lote);
            $pagina++;

            // Pausa entre paginas, por la misma razon que entre fichas.
            usleep(700_000);
        }

        return $todas;
    }

    /**
     * Extrae de una ficha lo que se puede leer con certeza.
     *
     * @return array<string, mixed>|null null si no merece la pena importarla
     */
    private function interpretar(array $ficha): ?array
    {
        $titulo = trim(html_entity_decode($ficha['title']['rendered'] ?? '', ENT_QUOTES | ENT_HTML5));
        $html = $ficha['content']['rendered'] ?? '';

        $texto = html_entity_decode(
            strip_tags(str_replace(['<br />', '<br>', '</p>'], "\n", $html)),
            ENT_QUOTES | ENT_HTML5
        );

        $lineas = array_values(array_filter(array_map('trim', explode("\n", $texto)), 'strlen'));
        $cabecera = implode("\n", array_slice($lineas, 0, 4));

        // Sin referencia no hay tipo fiable ni forma de evitar duplicados.
        if (! preg_match('/Ref\.?\s*([0-9]{2,5})\s*-\s*([A-Z])/iu', $cabecera, $ref)) {
            return null;
        }

        $sufijo = strtoupper($ref[2]);
        $tipoSlug = self::TIPOS[$sufijo] ?? null;

        if ($tipoSlug === null) {
            return null;
        }

        // Vendidas y rentadas se importan igual —son prueba social y el sitio
        // nuevo las muestra sin contacto— pero con su estado real.
        $estado = PropertyStatus::Available;
        if (preg_match('/\bVENDIDO\b/iu', $cabecera)) {
            $estado = PropertyStatus::Sold;
        } elseif (preg_match('/\bRENTADO\b/iu', $cabecera)) {
            $estado = PropertyStatus::Rented;
        }

        $operacion = preg_match('/\bRenta\b/iu', $cabecera) || $estado === PropertyStatus::Rented
            ? OperationType::Rent
            : OperationType::Sale;

        [$precio, $moneda, $porMetro] = $this->leerPrecio($cabecera);

        return [
            'reference_code' => strtoupper($ref[1].'-'.$sufijo),
            'wp_id' => $ficha['id'] ?? null,
            'title' => $titulo,
            'por_metro' => $porMetro,
            'descripcion' => $this->limpiarDescripcion($lineas, $porMetro),
            'tipo' => $tipoSlug,
            'ubicacion' => $this->leerUbicacion($lineas),
            'status' => $estado,
            'operation_type' => $operacion,
            'price' => $precio,
            'currency' => $moneda,
            'construction_area' => $this->leerMetros($texto, 'Construcci[oó]n'),
            'land_area' => $this->leerMetros($texto, 'Solar|Terreno'),

            // Un solar no tiene habitaciones ni banos por mucho que el texto
            // mencione «bano de servicio» del proyecto vecino.
            'bedrooms' => $this->esResidencial($tipoSlug) ? $this->leerHabitaciones($titulo, $texto) : null,
            'bathrooms' => $this->esResidencial($tipoSlug) ? $this->leerBanos($titulo, $texto) : null,
            'parking_spaces' => $this->esResidencial($tipoSlug) ? $this->leerParqueos($texto) : null,

            // FECHA REAL DE PUBLICACION, no la de la importacion.
            //
            // Con now() las 25 propiedades quedaban con el mismo instante y el
            // listado «mas recientes» acababa ordenando por id, o sea por el
            // orden en que las descargo el importador. La fecha del sitio
            // anterior es la unica que significa «cuando se anadio».
            'published_at' => isset($ficha['date'])
                ? Carbon::parse($ficha['date'])
                : now(),

            'link' => $ficha['link'] ?? null,
        ];
    }

    private function esResidencial(string $tipoSlug): bool
    {
        return in_array($tipoSlug, ['villa', 'apartamento', 'casa', 'penthouse'], true);
    }

    /**
     * Habitaciones: primero en el TITULO, que es donde el dato aparece limpio
     * («Apartamentos nuevos 1 Habitación», «Apartamento 2 habitaciones»).
     *
     * En el cuerpo el texto describe la casa habitacion por habitacion —«Dos
     * habitaciones con bano y terraza», «Habitacion principal con jacuzzi»— y
     * contar ahi da cifras que no son el total. Solo se recurre al cuerpo
     * cuando el titulo no dice nada, y se toma el mayor numero mencionado.
     */
    private function leerHabitaciones(string $titulo, string $texto): ?int
    {
        foreach ([$titulo, $texto] as $fuente) {
            if (preg_match_all('/(\d{1,2})\s*(?:hab|habitacion|habitaciones|habs?|dormitorios?)\b/iu', $fuente, $m)) {
                $valores = array_map('intval', $m[1]);
                $mayor = max($valores);

                if ($mayor >= 1 && $mayor <= 12) {
                    return $mayor;
                }
            }
        }

        return null;
    }

    /**
     * Banos, SOLO cuando la ficha da una cifra explicita.
     *
     * El sitio anterior casi nunca da el total: describe la casa habitacion
     * por habitacion —«Baño de visita», «Habitación principal con baño»,
     * «2 habitaciones secundarias con baño», «Medio baño en área social»—.
     * Contar esas menciones parece facil y da numeros equivocados: en una
     * ficha real el recuento sale 5 cuando la respuesta es 4,5.
     *
     * Publicar un numero de banos inventado en una inmobiliaria es peor que
     * no publicarlo: el cliente lo lee, va a ver la casa y encuentra otra
     * cosa. Asi que solo se aceptan dos formas inequivocas:
     *
     *     «2.5 baños»        el numero delante
     *     «Baños 1.5 y 2.5»  el numero detras (se toma el primero)
     *
     * El resto se deja vacio y lo rellena quien conoce la propiedad.
     */
    private function leerBanos(string $titulo, string $texto): ?float
    {
        $patrones = [
            '/(\d{1,2}(?:[.,]\d)?)\s*ba[ñn]os?\b/iu',
            '/\bba[ñn]os?\s*:?\s*(\d{1,2}(?:[.,]\d)?)/iu',
        ];

        foreach ([$titulo, $texto] as $fuente) {
            foreach ($patrones as $patron) {
                if (! preg_match_all($patron, $fuente, $m)) {
                    continue;
                }

                $valores = array_filter(
                    array_map(fn ($v) => (float) str_replace(',', '.', $v), $m[1]),
                    fn ($v) => $v >= 0.5 && $v <= 12
                );

                if ($valores !== []) {
                    return max($valores);
                }
            }
        }

        return null;
    }

    private function leerParqueos(string $texto): ?int
    {
        if (preg_match('/(?:marquesina|parqueos?|estacionamientos?)[^.\n]{0,30}?(\d{1,2})\s*(?:veh|carro|auto|parqueo)/iu', $texto, $m)
            || preg_match('/(\d{1,2})\s*(?:parqueos?|estacionamientos?)\b/iu', $texto, $m)) {
            $n = (int) $m[1];

            return $n >= 1 && $n <= 12 ? $n : null;
        }

        return null;
    }

    /**
     * @return array{0: ?float, 1: Currency, 2: ?string}
     *                                                   El tercer elemento es el precio POR METRO tal
     *                                                   cual venia escrito, cuando la ficha lo expresa
     *                                                   asi. Ver mas abajo.
     */
    private function leerPrecio(string $cabecera): array
    {
        // Se admiten «Inversión US$380,000», «Inversión Desde: US$203,000»,
        // «Renta: US$1,400.00» y «Inversión RD$35,000,000».
        if (! preg_match('/(US|RD)\s*\$\s*([\d.,]+)/iu', $cabecera, $m)) {
            return [null, Currency::USD, null];
        }

        $moneda = strtoupper($m[1]) === 'RD' ? Currency::DOP : Currency::USD;

        // Los miles van con coma y los decimales con punto.
        $numero = (float) str_replace(',', '', $m[2]);

        // PRECIO POR METRO CUADRADO.
        //
        // Varios terrenos dicen «Inversión US$55.00 x m²» o «RD$6,700 x m²».
        // Guardar 55 como precio de un terreno de 30.491 m² no es un redondeo
        // desafortunado: es publicar una villa de casi dos millones a 55
        // dolares. Y multiplicar por la superficie tampoco vale, porque seria
        // una cifra que nadie del negocio ha aprobado.
        //
        // Se deja el precio VACIO —la ficha dira «precio a consultar»— y el
        // dato literal se conserva al principio de la descripcion para que no
        // se pierda. El comando los enumera al final para que se revisen.
        if (preg_match('/'.preg_quote($m[0], '/').'\s*(?:x|por|\/)\s*m\s*(?:2|²)/iu', $cabecera)) {
            return [null, $moneda, trim($m[0]).' por m²'];
        }

        return [$numero > 0 ? $numero : null, $moneda, null];
    }

    private function leerUbicacion(array $lineas): ?string
    {
        foreach ($lineas as $linea) {
            if (str_contains($linea, '📍')) {
                return trim(str_replace('📍', '', $linea)) ?: null;
            }
        }

        return null;
    }

    /**
     * @param  string  $etiqueta  Alternancia SIN agrupar: se agrupa aqui.
     *                            Sin el (?:…), un patron como 'Solar|Terreno'
     *                            se lee como «Solar» O «Terreno seguido de un
     *                            numero», y el primer grupo queda sin definir.
     */
    private function leerMetros(string $texto, string $etiqueta): ?float
    {
        if (preg_match('/(?:'.$etiqueta.')\s*:?\s*([\d.,]+)\s*(?:m2|m²)/iu', $texto, $m)) {
            $valor = (float) str_replace(',', '', $m[1]);

            return $valor > 0 ? $valor : null;
        }

        return null;
    }

    /**
     * Descripcion sin la cabecera de ubicacion, referencia y precio: esos tres
     * datos ya viven en sus propias columnas y repetirlos en el texto se ve
     * como un copiar y pegar mal hecho.
     */
    private function limpiarDescripcion(array $lineas, ?string $porMetro = null): string
    {
        $utiles = array_filter($lineas, function (string $l) {
            return ! str_contains($l, '📍')
                && ! preg_match('/^Ref\.?\s*\d/iu', $l)
                && ! preg_match('/^(Inversi[oó]n|Renta|Precio|VENDIDO|RENTADO)/iu', $l);
        });

        return trim(implode("\n\n", array_slice(array_values($utiles), 0, 12)));
    }

    private function crear(array $datos, PropertyService $servicio, ?Property $existente): Property
    {
        $tipo = PropertyType::where('slug', $datos['tipo'])->first()
            ?? PropertyType::first();

        $province = $this->adivinarProvincia($datos['ubicacion']);

        $atributos = [
            'reference_code' => $datos['reference_code'],
            'property_type_id' => $tipo->id,
            'operation_type' => $datos['operation_type'],
            'status' => $datos['status'],
            'price' => $datos['price'],
            'currency' => $datos['currency'],
            'province_id' => $province?->id,
            'address' => $datos['ubicacion'],
            'construction_area' => $datos['construction_area'],
            'land_area' => $datos['land_area'],
            'bedrooms' => $datos['bedrooms'],
            'bathrooms' => $datos['bathrooms'],
            'parking_spaces' => $datos['parking_spaces'],
            'published_at' => $datos['published_at'],
        ];

        $property = $existente
            ? tap($existente)->update($atributos)
            : Property::create($atributos);

        $servicio->syncTranslations($property, [
            'es' => [
                'title' => $datos['title'],
                'description' => $datos['descripcion'],
            ],
        ]);

        return $property->fresh();
    }

    /**
     * La ubicacion del sitio viejo es un texto suelto («Juan Dolio», «La
     * Esperilla»), no una provincia. Se busca por coincidencia y, si no se
     * encuentra, se deja vacia: una provincia equivocada es peor que ninguna,
     * porque el filtro del listado la daria por buena.
     */
    private function adivinarProvincia(?string $ubicacion): ?Province
    {
        if (! $ubicacion) {
            return null;
        }

        foreach (Province::all() as $province) {
            if (Str::contains(Str::lower($ubicacion), Str::lower($province->name))) {
                return $province;
            }
        }

        return null;
    }

    /**
     * Descarga la galeria de la ficha y la guarda como webp.
     *
     * Las fotos se distinguen por la clase 'rem-slider-image': el resto de
     * imagenes de la pagina son el logo, los widgets laterales y las
     * miniaturas de OTRAS propiedades, que acabarian mezcladas en la galeria.
     */
    private function importarImagenes(Property $property, ?string $url): int
    {
        if (! $url) {
            return 0;
        }

        try {
            $html = Http::timeout(45)
                ->withHeaders(['User-Agent' => 'ERA Realty RD importer'])
                ->get($url)
                ->body();
        } catch (\Throwable) {
            return 0;
        }

        preg_match_all(
            '/class="[^"]*rem-slider-image[^"]*"[^>]*src="([^"]+\.(?:jpe?g|png|webp))"/i',
            $html,
            $m
        );

        $urls = array_values(array_unique($m[1]));

        if ($urls === []) {
            return 0;
        }

        // Se borran los FICHEROS ademas de las filas.
        //
        // Con solo ->delete() las fotos viejas se quedaban en disco: cada
        // `--force` genera nombres aleatorios nuevos, asi que tras tres pasadas
        // habia 1.477 ficheros para 488 referenciados. Casi 1.000 huerfanos
        // que nadie iba a echar de menos hasta llenar el disco.
        foreach ($property->images as $vieja) {
            foreach ($vieja->allPaths() as $ruta) {
                Storage::disk('public')->delete($ruta);
            }
        }

        $property->images()->delete();

        $guardadas = 0;
        $maximo = (int) $this->option('images');

        foreach (array_slice($urls, 0, $maximo) as $i => $origen) {
            if ($this->guardarImagen($property, $origen, $i)) {
                $guardadas++;
            }
        }

        return $guardadas;
    }

    private function guardarImagen(Property $property, string $url, int $orden): bool
    {
        try {
            $respuesta = Http::timeout(60)
                ->withHeaders(['User-Agent' => 'ERA Realty RD importer'])
                ->get($url);

            if ($respuesta->failed()) {
                return false;
            }

            $imagen = $this->imagenes->read($respuesta->body());

            // Se descartan las miniaturas: una foto de 300 px en la galeria de
            // una villa de un millon de dolares se ve peor que no tener foto.
            //
            // El umbral mira el LADO MAYOR, no el ancho. Filtrar por ancho
            // descarta las fotos verticales —muchas del sitio viejo son
            // 576x768, tomadas con el movil en vertical— que son perfectamente
            // publicables. Con el ancho como criterio, varias propiedades se
            // importaron sin una sola foto.
            if (max($imagen->width(), $imagen->height()) < 700) {
                return false;
            }

            if ($imagen->width() > 1920) {
                $imagen->scaleDown(width: 1920);
            }

            $nombre = 'properties/'.$property->id.'/'.Str::random(24);

            Storage::disk('public')->put($nombre.'.webp', (string) $imagen->toWebp(82));

            $miniatura = $this->imagenes->read($respuesta->body())->cover(600, 400);
            Storage::disk('public')->put($nombre.'-thumb.webp', (string) $miniatura->toWebp(78));

            PropertyImage::create([
                'property_id' => $property->id,
                'path' => $nombre.'.webp',
                'webp_path' => $nombre.'.webp',
                'thumbnail_path' => $nombre.'-thumb.webp',
                'original_name' => basename(parse_url($url, PHP_URL_PATH)),
                'is_main' => $orden === 0,
                'sort_order' => $orden,
                'width' => $imagen->width(),
                'height' => $imagen->height(),
                'size' => strlen($respuesta->body()),
                'mime_type' => 'image/webp',
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->line('    <fg=yellow>foto omitida</> '.Str::limit($e->getMessage(), 60));

            return false;
        }
    }
}
