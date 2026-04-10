<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use App\Enums\TrType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TechnicalRequirement extends Model
{
    use HasFactory;

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

    public function testCases(): BelongsToMany
    {
        return $this->belongsToMany(TestCase::class, 'tr_test_case');
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

    public function getShowUrl(): string
    {
        return route('projects.requirements.technical.show', [$this->project_id, $this->id]);
    }
}
