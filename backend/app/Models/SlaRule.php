<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaRule extends Model
{
    protected $table = 'sla_rules';

    protected $fillable = [
        'priority_id',
        'first_response_minutes',
        'resolution_minutes',
        'pause_on_waiting_customer',
        'use_business_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pause_on_waiting_customer' => 'boolean',
            'use_business_hours' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }
}
