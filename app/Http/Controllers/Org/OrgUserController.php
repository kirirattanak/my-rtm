<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrgUserController extends Controller
{
    private function orgOrFail(Request $request): Organization
    {
        $user = $request->user();
        $org  = $user->organization;

        abort_unless($org && ($user->isAdmin() || $user->isOrgOwner()), 403);

        return $org;
    }

    public function index(Request $request): Response
    {
        $org = $this->orgOrFail($request);
        $org->load('activeSubscription.tierOption');

        $members = $org->users()
            ->with('role')
            ->select('users.id', 'users.name', 'users.email', 'users.is_active', 'users.role_id', 'users.organization_id')
            ->orderBy('users.name')
            ->get()
            ->map(fn (User $u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'is_active' => $u->is_active,
                'role_id'   => $u->role_id,
                'role_name' => $u->role?->name,
                'is_owner'  => $org->owner_id === $u->id,
            ]);

        $roles = Role::whereNull('organization_id')
            ->orWhere('organization_id', $org->id)
            ->where('slug', '!=', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_system', 'organization_id']);

        return Inertia::render('org/Users', [
            'members'      => $members,
            'roles'        => $roles,
            'seats'        => $org->activeSubscription?->tierOption?->seats,
            'seats_used'   => $org->activeSeatCount(),
            'pending_invitations' => Invitation::where('organization_id', $org->id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->get(['id', 'email', 'expires_at']),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        $org->load('activeSubscription.tierOption');

        $seats = $org->activeSubscription?->tierOption?->seats;
        if ($seats !== null && $org->activeSeatCount() >= $seats) {
            return back()->withErrors(['email' => "Your plan allows up to {$seats} users. Upgrade your seat plan to invite more."]);
        }

        $data = $request->validate([
            'email'   => 'required|email',
            'role_id' => 'required|exists:roles,id',
        ]);

        Invitation::updateOrCreate(
            ['email' => $data['email'], 'organization_id' => $org->id],
            [
                'role'        => Role::find($data['role_id'])?->slug ?? 'viewer',
                'token'       => \Illuminate\Support\Str::random(40),
                'invited_by'  => $request->user()->id,
                'accepted_at' => null,
                'expires_at'  => now()->addDays(7),
            ]
        );

        return back()->with('success', "Invitation sent to {$data['email']}.");
    }

    public function revokeInvite(Request $request, Invitation $invitation): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($invitation->organization_id === $org->id, 403);

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($user->organization_id === $org->id, 403);
        abort_if($org->owner_id === $user->id, 403, 'Cannot change the org owner\'s role.');

        $data = $request->validate(['role_id' => 'required|exists:roles,id']);

        $role = Role::findOrFail($data['role_id']);
        abort_if($role->isAdmin(), 403, 'Cannot assign the system admin role.');

        $user->update(['role_id' => $data['role_id']]);

        return back()->with('success', 'Role updated.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($user->organization_id === $org->id, 403);
        abort_if($org->owner_id === $user->id, 403, 'Cannot deactivate the org owner.');

        if (!$user->is_active) {
            $org->load('activeSubscription.tierOption');
            $seats = $org->activeSubscription?->tierOption?->seats;
            if ($seats !== null && $org->activeSeatCount() >= $seats) {
                return back()->withErrors(['active' => "Seat limit reached. Upgrade your plan to reactivate users."]);
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', $user->is_active ? 'User reactivated.' : 'User deactivated.');
    }
}
