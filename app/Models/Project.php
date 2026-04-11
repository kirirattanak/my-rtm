<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'owner_id',
        'start_date',
        'target_date',
    ];

    protected function casts(): array
    {
        return [
            'status'      => ProjectStatus::class,
            'start_date'  => 'date',
            'target_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function memberRole(User $user): ?Role
    {
        $member = $this->projectMembers()
            ->where('user_id', $user->id)
            ->with('role')
            ->first();

        return $member?->role;
    }

    public function hasMember(User $user): bool
    {
        return $this->projectMembers()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function businessRequirements(): HasMany
    {
        return $this->hasMany(BusinessRequirement::class);
    }

    public function technicalRequirements(): HasMany
    {
        return $this->hasMany(TechnicalRequirement::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderBy('start_date');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
