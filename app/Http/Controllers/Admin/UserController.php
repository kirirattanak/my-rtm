<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at->toDateString(),
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'roles' => collect(UserRole::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('updateRole', User::class);

        $request->validate([
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $user->update(['role' => $request->role]);

        return back()->with('success', "{$user->name}'s role updated to {$user->role->label()}.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$status}.");
    }
}
