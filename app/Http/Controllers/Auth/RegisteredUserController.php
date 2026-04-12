<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        $invitation = null;

        if ($request->filled('invitation')) {
            $invitation = Invitation::where('token', $request->invitation)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();
        }

        return Inertia::render('auth/Register', [
            'invitation' => $invitation ? [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role'  => $invitation->role,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $invitation = null;
        $roleId = Role::where('slug', 'viewer')->value('id');

        if ($request->filled('invitation_token')) {
            $invitation = Invitation::where('token', $request->invitation_token)
                ->where('email', $request->email)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($invitation) {
                $roleId = Role::where('slug', $invitation->role)->value('id') ?? $roleId;
            }
        }

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'role_id'         => $roleId,
            'organization_id' => $invitation?->organization_id,
        ]);

        if ($invitation) {
            $invitation->update(['accepted_at' => now()]);
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
