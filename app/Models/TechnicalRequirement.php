<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use App\Enums\TrType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TechnicalRequirement extends Model
{
    protected $fillable = [
        'project_id',
        'number',
        'title',
        'description',
        'type',
        'status',
        'created_by',
    ];

    protected $casts = [
        'type'   => TrType::class,
        'status' => RequirementStatus::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function businessRequirements(): BelongsToMany
    {
        return $this->belongsToMany(BusinessRequirement::class, 'br_tr');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function getRefAttribute(): string
    {
        return 'TR-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }
}
