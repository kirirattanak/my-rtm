<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'organization_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    /**
     * True when this user is the owner of their organisation.
     * Org owners have full access within their org's projects.
     */
    public function isOrgOwner(): bool
    {
        $org = $this->relationLoaded('organization') ? $this->organization : $this->organization()->first();
        return $org && $org->owner_id === $this->id;
    }

    /**
     * Check whether the user has a given permission, optionally scoped to a project.
     *
     * Bypass order:
     *  1. System admin — global bypass
     *  2. Org owner   — bypass for any project that belongs to their org
     *  3. Role-based check (project-level override takes precedence over global role)
     */
    public function hasPermission(string $key, ?Project $project = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($project !== null && $this->isOrgOwner()) {
            $orgId = $this->relationLoaded('organization')
                ? $this->organization?->id
                : $this->organization()->value('id');
            if ($project->organization_id === $orgId) {
                return true;
            }
        }

        $role = $project !== null ? $this->effectiveRole($project) : $this->role;

        if (!$role) {
            return false;
        }

        return in_array($key, $this->cachedPermissions($role));
    }

    /**
     * Return the user's effective Role for the given project.
     * Uses the project-level role override if set, otherwise falls back to the global role.
     * Returns null if the user is not a project member.
     */
    public function effectiveRole(Project $project): ?Role
    {
        $member = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $this->id)
            ->with('role')
            ->first();

        if (!$member) {
            return null;
        }

        return $member->role ?? $this->role;
    }

    private function cachedPermissions(Role $role): array
    {
        return Cache::remember(
            "role.{$role->id}.permissions",
            now()->addHour(),
            fn () => $role->permissions()->pluck('key')->toArray()
        );
    }
}
