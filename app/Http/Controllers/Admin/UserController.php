<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role_id'    => $user->role_id,
                'role_name'  => $user->role?->name,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at->toDateString(),
            ]);

        $roles = Role::orderBy('name')->get(['id', 'name', 'is_system']);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function updateRole(UserRoleRequest $request, User $user): RedirectResponse
    {
        $this->authorize('updateRole', User::class);

        $data = $request->validated();

        $user->update(['role_id' => $data['role_id']]);

        return back()->with('success', "{$user->name}'s role updated to {$user->role->name}.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$status}.");
    }
}
