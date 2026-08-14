<?php

namespace App\Modules\WhatsApp\Models;

use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappClick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'property_id', 'source', 'phone_number', 'generated_message',
        'ip_address', 'user_agent', 'referrer_url',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
