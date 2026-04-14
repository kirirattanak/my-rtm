<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintVelocity extends Model
{
    protected $fillable = [
        'sprint_id',
        'pert_expected_hours',
        'pert_std_dev',
        'available_hours',
        'actual_hours_logged',
        'br_count_committed',
        'br_count_completed',
    ];

    protected $casts = [
        'pert_expected_hours' => 'decimal:2',
        'pert_std_dev'        => 'decimal:2',
        'available_hours'     => 'decimal:2',
        'actual_hours_logged' => 'decimal:2',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}
