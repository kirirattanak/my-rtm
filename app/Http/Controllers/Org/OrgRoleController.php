<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrgRoleController extends Controller
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

        $roles = Role::with('permissions')
            ->where('slug', '!=', 'admin')
            ->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $org->id))
            ->orderByRaw("is_system DESC, name ASC")
            ->get()
            ->map(fn (Role $r) => [
                'id'              => $r->id,
                'name'            => $r->name,
                'slug'            => $r->slug,
                'is_system'       => $r->is_system,
                'is_org_scoped'   => $r->isOrgScoped(),
                'user_count'      => $r->users()->where('organization_id', $org->id)->count(),
                'permission_count' => $r->permissions->count(),
            ]);

        return Inertia::render('org/Roles', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $this->orgOrFail($request);

        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        Role::create([
            'name'            => $data['name'],
            'slug'            => Str::slug($data['name']) . '-' . $org->slug,
            'is_system'       => false,
            'organization_id' => $org->id,
            'created_by'      => $request->user()->id,
        ]);

        return back()->with('success', 'Custom role created.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($role->organization_id === $org->id, 403);

        $data = $request->validate(['name' => 'required|string|max:100']);

        $role->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . $org->slug,
        ]);

        Cache::forget("role.{$role->id}.permissions");

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($role->organization_id === $org->id, 403);

        if ($role->users()->where('organization_id', $org->id)->exists()) {
            return back()->withErrors(['role' => 'Cannot delete a role that is assigned to users.']);
        }

        Cache::forget("role.{$role->id}.permissions");
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function editPermissions(Request $request, Role $role): Response
    {
        $org = $this->orgOrFail($request);
        abort_if($role->slug === 'admin', 403);
        abort_unless($role->organization_id === $org->id || $role->is_system, 403);

        $groups = Permission::orderBy('group')->orderBy('sort_order')->get()
            ->groupBy('group')
            ->map(fn ($perms, $group) => [
                'group'       => $group,
                'permissions' => $perms->map(fn ($p) => ['id' => $p->id, 'key' => $p->key, 'label' => $p->label]),
            ])
            ->values();

        return Inertia::render('org/RolePermissions', [
            'role'     => ['id' => $role->id, 'name' => $role->name, 'is_system' => $role->is_system, 'is_org_scoped' => $role->isOrgScoped()],
            'groups'   => $groups,
            'assigned' => $role->permissions()->pluck('permission_id')->toArray(),
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $org = $this->orgOrFail($request);
        abort_unless($role->organization_id === $org->id, 403);

        $data = $request->validate(['permissions' => 'array', 'permissions.*' => 'exists:permissions,id']);

        $role->permissions()->sync($data['permissions'] ?? []);
        Cache::forget("role.{$role->id}.permissions");

        return back()->with('success', 'Permissions updated.');
    }
}
