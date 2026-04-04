<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
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
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function memberRole(User $user): ?UserRole
    {
        $member = $this->projectMembers()
            ->where('user_id', $user->id)
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
}
