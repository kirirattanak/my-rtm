<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('invite', User::class);

        $user = $request->user();

        $invitations = Invitation::with(['inviter', 'organization'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organization_id', $user->organization_id))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invitation $inv) => [
                'id'           => $inv->id,
                'email'        => $inv->email,
                'role'         => $inv->role,
                'organization' => $inv->organization?->name,
                'invited_by'   => $inv->inviter->name,
                'status'       => $inv->isAccepted() ? 'accepted' : ($inv->isExpired() ? 'expired' : 'pending'),
                'expires_at'   => $inv->expires_at->toDateString(),
                'created_at'   => $inv->created_at->toDateString(),
            ]);

        return Inertia::render('admin/Invitations', [
            'invitations'   => $invitations,
            'roles'         => Role::orderBy('name')->get(['id', 'name', 'slug', 'is_system']),
            'organizations' => $user->isAdmin()
                ? Organization::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : [],
            'is_admin'      => $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('invite', User::class);

        $user = $request->user();

        $rules = [
            'email' => ['required', 'email', 'unique:users,email', 'unique:invitations,email'],
            'role'  => ['required', 'string', 'exists:roles,slug'],
        ];

        if ($user->isAdmin()) {
            $rules['organization_id'] = ['nullable', 'exists:organizations,id'];
        }

        $data = $request->validate($rules);

        $organizationId = $user->isAdmin()
            ? ($data['organization_id'] ?? null)
            : $user->organization_id;

        $invitation = Invitation::create([
            'email'           => $data['email'],
            'role'            => $data['role'],
            'token'           => Str::random(40),
            'organization_id' => $organizationId,
            'invited_by'      => $user->id,
            'expires_at'      => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationNotification($invitation));

        return back()->with('success', "Invitation sent to {$invitation->email}.");
    }

    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorize('invite', User::class);

        $user = $request->user();

        // Org owners can only revoke invitations from their own org
        if (! $user->isAdmin() && $invitation->organization_id !== $user->organization_id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }
}
