<?php

namespace App\Models;

use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'sprint_id',
        'taskable_type',
        'taskable_id',
        'title',
        'description',
        'effort_estimate',
        'effort_unit',
        'status',
        'due_date',
        'assignee_id',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'effort_estimate' => 'decimal:2',
        'effort_unit'     => EffortUnit::class,
        'status'          => TaskStatus::class,
        'due_date'        => 'date',
        'completed_at'    => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class)->latest();
    }

    /**
     * Actual effort in hours logged by users.
     */
    public function totalLoggedHours(): float
    {
        return (float) $this->logs()->sum('hours');
    }

    /**
     * Actual effort: logged hours if any, else effort_estimate (when done).
     * Returns null if neither is available.
     */
    public function effectiveActualHours(): ?float
    {
        $logged = $this->totalLoggedHours();
        if ($logged > 0) return $logged;
        if ($this->status === TaskStatus::Done && $this->effort_unit === EffortUnit::Hours && $this->effort_estimate !== null) {
            return (float) $this->effort_estimate;
        }
        return null;
    }
}
