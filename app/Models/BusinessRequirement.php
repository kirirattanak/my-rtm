<?php

namespace App\Models;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BusinessRequirement extends Model
{
    use HasFactory;

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
        return 'BR-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }

    public function getShowUrl(): string
    {
        return route('projects.requirements.business.show', [$this->project_id, $this->id]);
    }
}
