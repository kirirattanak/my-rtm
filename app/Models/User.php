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
     * Check whether the user has a given permission, optionally scoped to a project.
     *
     * When a project is supplied the user must be a project member; their
     * project-level role override (if any) takes precedence over their global role.
     * Admin always bypasses all checks.
     */
    public function hasPermission(string $key, ?Project $project = null): bool
    {
        if ($this->isAdmin()) {
            return true;
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
