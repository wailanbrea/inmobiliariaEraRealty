<?php

namespace App\Modules\Leads\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\User;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'source', 'name', 'phone', 'email', 'message', 'details',
        'property_id', 'interest_type', 'budget_range', 'preferred_contact', 'status',
        'assigned_to_user_id', 'admin_notes', 'contacted_at',
        'ip_address', 'user_agent', 'referrer_url',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'details' => 'array',
            'contacted_at' => 'datetime',
        ];
    }
}
