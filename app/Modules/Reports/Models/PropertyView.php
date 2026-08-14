<?php

namespace App\Modules\Reports\Models;

use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contador de visitas por propiedad y dia.
 *
 * Sin timestamps: la fecha ES el dato. Ver la migracion para el porque de
 * agregar por dia en vez de guardar una fila por visita.
 */
class PropertyView extends Model
{
    public $timestamps = false;

    protected $fillable = ['property_id', 'viewed_on', 'views'];

    protected function casts(): array
    {
        return [
            'viewed_on' => 'date',
            'views' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
