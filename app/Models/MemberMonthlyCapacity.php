<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMonthlyCapacity extends Model
{
    protected $table = 'member_monthly_capacity';

    protected $fillable = [
        'user_id',
        'project_id',
        'year',
        'month',
        'available_hours',
        'focus_factor',
        'notes',
    ];

    protected $casts = [
        'available_hours' => 'decimal:2',
        'focus_factor'    => 'decimal:2',
        'year'            => 'integer',
        'month'           => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function effectiveHours(): float
    {
        return round((float) $this->available_hours * (float) $this->focus_factor, 2);
    }
}
