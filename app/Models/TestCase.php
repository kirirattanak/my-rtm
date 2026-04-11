<?php

namespace App\Models;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use App\Models\Concerns\HasStatusCounts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TestCase extends Model
{
    use HasFactory, HasStatusCounts;

    protected $fillable = [
        'project_id',
        'number',
        'title',
        'description',
        'steps',
        'expected_result',
        'type',
        'priority',
        'status',
        'assignee_id',
        'created_by',
    ];

    protected $casts = [
        'type'     => TestCaseType::class,
        'priority' => BrPriority::class,
        'status'   => RequirementStatus::class,
        'steps'    => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function technicalRequirements(): BelongsToMany
    {
        return $this->belongsToMany(TechnicalRequirement::class, 'tr_test_case');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class)->latest();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function getRefAttribute(): string
    {
        return 'TC-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }

    public function getLatestStatusAttribute(): ?string
    {
        return $this->runs()->value('status');
    }

    public function isPassing(): bool
    {
        return $this->runs()->where('status', 'pass')->exists();
    }
}
