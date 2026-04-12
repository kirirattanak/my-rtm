<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\SubscriptionTierOption;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        $organizations = Organization::with(['owner', 'activeSubscription.tierOption'])
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $org) => [
                'id'         => $org->id,
                'name'       => $org->name,
                'slug'       => $org->slug,
                'is_active'  => $org->is_active,
                'owner'      => $org->owner ? ['id' => $org->owner->id, 'name' => $org->owner->name] : null,
                'tier'       => $org->activeSubscription?->tierOption?->tier,
                'tier_label' => $org->activeSubscription?->tierOption?->label,
                'status'     => $org->activeSubscription?->status,
                'seats'      => $org->activeSubscription?->tierOption?->seats,
                'seats_used' => $org->activeSeatCount(),
                'users_count' => $org->users_count,
                'created_at' => $org->created_at->toDateString(),
            ]);

        return Inertia::render('admin/Organizations', [
            'organizations' => $organizations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/OrganizationForm', [
            'tierOptions' => SubscriptionTierOption::orderBy('sort_order')->get(),
            'users'       => User::select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:organizations,slug|regex:/^[a-z0-9\-]+$/',
            'tier_option_id'    => 'required|exists:subscription_tier_options,id',
            'is_active'         => 'boolean',
            // Owner: provide either an existing user id OR name+email for a new account
            'owner_id'          => 'nullable|exists:users,id',
            'owner_name'        => 'required_without:owner_id|string|max:255',
            'owner_email'       => 'required_without:owner_id|email|unique:users,email',
        ]);

        // Create the org first (owner set after so the user can reference org_id)
        $org = Organization::create([
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'owner_id'  => null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $org->subscriptions()->create([
            'tier_option_id' => $data['tier_option_id'],
            'status'         => 'active',
            'starts_at'      => now(),
        ]);

        if (!empty($data['owner_id'])) {
            // Assign existing user as owner
            $ownerId = $data['owner_id'];
            User::where('id', $ownerId)->update(['organization_id' => $org->id]);
        } else {
            // Create a new owner account
            $pmRole = Role::where('slug', 'project_manager')->first();
            $owner  = User::create([
                'name'              => $data['owner_name'],
                'email'             => $data['owner_email'],
                'password'          => Hash::make(Str::random(16)),
                'role_id'           => $pmRole?->id,
                'organization_id'   => $org->id,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
            $ownerId = $owner->id;
        }

        $org->update(['owner_id' => $ownerId]);

        return redirect()->route('admin.organizations.show', $org)
            ->with('success', "Organisation \"{$org->name}\" created. Owner account is ready.");
    }

    public function show(Organization $organization): Response
    {
        $organization->load(['owner', 'activeSubscription.tierOption']);

        return Inertia::render('admin/OrganizationShow', [
            'organization'  => [
                'id'        => $organization->id,
                'name'      => $organization->name,
                'slug'      => $organization->slug,
                'is_active' => $organization->is_active,
                'owner'     => $organization->owner
                    ? ['id' => $organization->owner->id, 'name' => $organization->owner->name, 'email' => $organization->owner->email]
                    : null,
                'created_at' => $organization->created_at->toDateString(),
            ],
            'subscription'  => $organization->activeSubscription ? [
                'id'            => $organization->activeSubscription->id,
                'status'        => $organization->activeSubscription->status,
                'tier'          => $organization->activeSubscription->tierOption->tier,
                'tier_label'    => $organization->activeSubscription->tierOption->label,
                'seats'         => $organization->activeSubscription->tierOption->seats,
                'seats_used'    => $organization->activeSeatCount(),
                'starts_at'     => $organization->activeSubscription->starts_at->toDateString(),
                'ends_at'       => $organization->activeSubscription->ends_at?->toDateString(),
                'trial_ends_at' => $organization->activeSubscription->trial_ends_at?->toDateString(),
            ] : null,
            'members'       => $organization->users()
                ->with('role')
                ->select('users.id', 'users.name', 'users.email', 'users.is_active', 'users.role_id')
                ->orderBy('users.name')
                ->get()
                ->map(fn (User $u) => [
                    'id'        => $u->id,
                    'name'      => $u->name,
                    'email'     => $u->email,
                    'is_active' => $u->is_active,
                    'role'      => $u->role?->name,
                ]),
            'tierOptions'   => SubscriptionTierOption::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'owner_id'  => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $organization->update($data);

        return back()->with('success', 'Organisation updated.');
    }

    public function updateSubscription(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'tier_option_id' => 'required|exists:subscription_tier_options,id',
            'status'         => 'required|in:trial,active,expired,cancelled',
        ]);

        // Close current subscription and open a new one
        $organization->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->update(['status' => 'cancelled', 'ends_at' => now()]);

        $organization->subscriptions()->create([
            'tier_option_id' => $data['tier_option_id'],
            'status'         => $data['status'],
            'starts_at'      => now(),
        ]);

        return back()->with('success', 'Subscription updated.');
    }
}
