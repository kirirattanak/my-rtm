<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'start_date',
        'end_date',
        'capacity',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'capacity'   => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    public function totalPlannedEffort(string $unit): float
    {
        return (float) $this->tasks()
            ->where('effort_unit', $unit)
            ->whereNotNull('effort_estimate')
            ->sum('effort_estimate');
    }
}
