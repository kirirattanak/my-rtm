<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolePermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function edit(Role $role): Response
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $permissions = Permission::orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn ($perms, $group) => [
                'group'       => $group,
                'permissions' => $perms->map(fn ($p) => [
                    'id'    => $p->id,
                    'key'   => $p->key,
                    'label' => $p->label,
                ]),
            ])
            ->values();

        $assigned = $role->permissions()->pluck('permission_id')->toArray();

        return Inertia::render('admin/RolePermissions', [
            'role'        => [
                'id'        => $role->id,
                'name'      => $role->name,
                'is_system' => $role->is_system,
                'is_admin'  => $role->isAdmin(),
            ],
            'groups'      => $permissions,
            'assigned'    => $assigned,
        ]);
    }

    public function update(RolePermissionRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        if ($role->isAdmin()) {
            return back()->with('error', 'Admin permissions cannot be modified.');
        }

        $data = $request->validated();

        $role->permissions()->sync($data['permissions']);

        Cache::forget("role.{$role->id}.permissions");

        return back()->with('success', 'Permissions updated.');
    }
}
