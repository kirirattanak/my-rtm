<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('invite', User::class);

        $invitations = Invitation::with('inviter')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invitation $inv) => [
                'id'          => $inv->id,
                'email'       => $inv->email,
                'role'        => $inv->role->value,
                'role_label'  => $inv->role->label(),
                'invited_by'  => $inv->inviter->name,
                'status'      => $inv->isAccepted() ? 'accepted' : ($inv->isExpired() ? 'expired' : 'pending'),
                'expires_at'  => $inv->expires_at->toDateString(),
                'created_at'  => $inv->created_at->toDateString(),
            ]);

        return Inertia::render('admin/Invitations', [
            'invitations' => $invitations,
            'roles'       => collect(UserRole::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('invite', User::class);

        $request->validate([
            'email' => ['required', 'email', 'unique:users,email', 'unique:invitations,email'],
            'role'  => ['required', Rule::in(UserRole::values())],
        ]);

        $invitation = Invitation::create([
            'email'      => $request->email,
            'role'       => $request->role,
            'token'      => Str::random(40),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationNotification($invitation));

        return back()->with('success', "Invitation sent to {$invitation->email}.");
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $this->authorize('invite', User::class);

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }
}
