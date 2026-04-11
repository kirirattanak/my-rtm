<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $roles = Role::withCount('users')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'         => $role->id,
                'name'       => $role->name,
                'slug'       => $role->slug,
                'is_system'  => $role->is_system,
                'user_count' => $role->users_count,
            ]);

        return Inertia::render('admin/Roles', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create([
            'name'       => $data['name'],
            'slug'       => Str::slug($data['name'], '_'),
            'is_system'  => false,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Role \"{$data['name']}\" created.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be renamed.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:roles,name,{$role->id}"],
        ]);

        $role->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name'], '_'),
        ]);

        return back()->with('success', 'Role renamed.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }
}
