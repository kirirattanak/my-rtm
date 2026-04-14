<?php

namespace App\Services;

use App\Models\Organization;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Determines which application features an organisation can access
 * based on their active subscription tier.
 *
 * Feature keys: br, tr, tc, test_runs, test_suites, sprints, rtm, reports, imports_exports
 *
 * Tier hierarchy (cumulative):
 *   basic    → projects, tasks, members
 *   starter  → + br, tr
 *   standard → + tc, test_runs, test_suites, sprints
 *   pro      → + rtm, reports, imports_exports
 */
class TierGate
{
    private const FEATURES = [
        'basic' => [
            'projects', 'tasks', 'members',
        ],
        'starter' => [
            'projects', 'tasks', 'members',
            'br', 'tr',
        ],
        'standard' => [
            'projects', 'tasks', 'members',
            'br', 'tr',
            'tc', 'test_runs', 'test_suites', 'sprints',
            'capacity_planning',
        ],
        'pro' => [
            'projects', 'tasks', 'members',
            'br', 'tr',
            'tc', 'test_runs', 'test_suites', 'sprints',
            'capacity_planning',
            'rtm', 'reports', 'imports_exports',
        ],
    ];

    /** Minimum tier required to unlock each feature. */
    private const UNLOCK_AT = [
        'projects'       => 'basic',
        'tasks'          => 'basic',
        'members'        => 'basic',
        'br'             => 'starter',
        'tr'             => 'starter',
        'tc'             => 'standard',
        'test_runs'      => 'standard',
        'test_suites'    => 'standard',
        'sprints'           => 'standard',
        'capacity_planning' => 'standard',
        'rtm'               => 'pro',
        'reports'        => 'pro',
        'imports_exports' => 'pro',
    ];

    public function __construct(private readonly Organization $organization) {}

    public static function for(Organization $organization): self
    {
        return new self($organization);
    }

    public function can(string $feature): bool
    {
        $tier = $this->activeTier();

        if ($tier === null) {
            return false;
        }

        return \in_array($feature, self::FEATURES[$tier] ?? [], true);
    }

    public function cannot(string $feature): bool
    {
        return !$this->can($feature);
    }

    /**
     * @throws HttpException 403 when the feature is not available on the current tier.
     */
    public function assertCan(string $feature): void
    {
        if (!$this->can($feature)) {
            $requiredTier = ucfirst(self::UNLOCK_AT[$feature] ?? 'a higher');
            abort(403, "This feature requires the {$requiredTier} plan or above.");
        }
    }

    public function activeTier(): ?string
    {
        $subscription = $this->organization->activeSubscription;

        if (!$subscription) {
            return null;
        }

        return $subscription->tierOption?->tier;
    }

    /** Returns the label of the tier that unlocks the given feature. */
    public static function unlockTier(string $feature): string
    {
        return ucfirst(self::UNLOCK_AT[$feature] ?? 'Pro');
    }

    /** Returns all features available for each tier (for UI display). */
    public static function featureMatrix(): array
    {
        return self::FEATURES;
    }
}
