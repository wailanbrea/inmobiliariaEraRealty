<?php

namespace App\Modules\Reports\Services;

use App\Modules\Properties\Models\Property;
use App\Modules\Reports\Models\PropertyView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ViewTracker
{
    /**
     * Suma una visita al contador del dia.
     *
     * Se resuelve con un UPSERT y no con "leer, decidir, escribir".
     *
     * Con dos visitantes abriendo la misma ficha en el mismo milisegundo, el
     * camino de leer-y-decidir hace que ambos vean "no existe fila", ambos
     * intenten insertar, y uno reviente contra el indice unico
     * (property_id, viewed_on) — un 500 en la ficha por culpa de la analitica.
     * El upsert delega ese desempate a la base de datos, que es quien puede
     * resolverlo de forma atomica.
     *
     * La expresion `views + 1` se escribe en SQL crudo a proposito: sumar en
     * PHP significaria leer el valor antes, y volveriamos al mismo problema.
     */
    public function record(Property $property, ?Carbon $fecha = null): void
    {
        $dia = ($fecha ?? Carbon::now())->toDateString();

        // La columna va SIN cualificar ('views', no 'property_views.views'):
        // asi la expresion es valida tanto en el ON DUPLICATE KEY UPDATE de
        // MySQL como en el ON CONFLICT DO UPDATE de SQLite, que es el motor
        // de las pruebas. En ambos, el 'views' de la derecha es el valor que
        // ya habia en la fila.
        PropertyView::query()->upsert(
            [['property_id' => $property->getKey(), 'viewed_on' => $dia, 'views' => 1]],
            ['property_id', 'viewed_on'],
            ['views' => DB::raw('views + 1')],
        );
    }
}
