<?php

namespace App\Models;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Models\Concerns\HasStatusCounts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BusinessRequirement extends Model
{
    use HasFactory, HasStatusCounts;

    protected $fillable = [
        'project_id',
        'number',
        'title',
        'description',
        'priority',
        'status',
        'category',
        'tags',
        'created_by',
        'optimistic_hours',
        'most_likely_hours',
        'pessimistic_hours',
    ];

    protected $casts = [
        'priority' => BrPriority::class,
        'status'   => RequirementStatus::class,
        'tags'     => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technicalRequirements(): BelongsToMany
    {
        return $this->belongsToMany(TechnicalRequirement::class, 'br_tr');
    }

    /** BRs that this BR is blocking — outgoing edges (this BR must be done first). */
    public function blockingBrs(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'br_dependencies', 'blocking_br_id', 'blocked_br_id');
    }

    /** BRs that are blocking this BR — incoming edges (prerequisites). */
    public function blockedByBrs(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'br_dependencies', 'blocked_br_id', 'blocking_br_id');
    }

    /** True when at least one prerequisite is not yet implemented. */
    public function getIsBlockedAttribute(): bool
    {
        return $this->relationLoaded('blockedByBrs')
            && $this->blockedByBrs->contains(fn ($br) => $br->status->value !== 'implemented');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'sprint_business_requirements')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    // ── PERT helpers ────────────────────────────────────────────────────────

    public function hasPertEstimate(): bool
    {
        return $this->optimistic_hours !== null
            && $this->most_likely_hours !== null
            && $this->pessimistic_hours !== null;
    }

    public function pertExpected(): ?float
    {
        if (! $this->hasPertEstimate()) {
            return null;
        }

        return ($this->optimistic_hours + 4 * $this->most_likely_hours + $this->pessimistic_hours) / 6;
    }

    public function pertVariance(): ?float
    {
        if (! $this->hasPertEstimate()) {
            return null;
        }

        return (($this->pessimistic_hours - $this->optimistic_hours) / 6) ** 2;
    }

    public function pertStdDev(): ?float
    {
        $variance = $this->pertVariance();

        return $variance !== null ? sqrt($variance) : null;
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getRefAttribute(): string
    {
        return 'BR-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }

    public function getShowUrl(): string
    {
        return route('projects.requirements.business.show', [$this->project_id, $this->id]);
    }
}
