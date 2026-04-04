<?php

namespace App\Http\Controllers\Projects;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('manageMembers', $project);

        $members = $project->projectMembers()->with('user')->get()->map(fn (ProjectMember $m) => [
            'id'         => $m->id,
            'user_id'    => $m->user_id,
            'name'       => $m->user->name,
            'email'      => $m->user->email,
            'role'       => $m->role->value,
            'role_label' => $m->role->label(),
        ]);

        $addableUsers = User::where('is_active', true)
            ->whereNotIn('id', $project->projectMembers()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('projects/Members', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'members' => $members,
            'addable_users' => $addableUsers,
            'roles' => collect(UserRole::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $request->validate([
            'user_id' => ['required', 'exists:users,id',
                Rule::unique('project_members')->where('project_id', $project->id)],
            'role'    => ['required', Rule::in(UserRole::values())],
        ]);

        $project->projectMembers()->create([
            'user_id' => $request->user_id,
            'role'    => $request->role,
        ]);

        return back()->with('success', 'Member added.');
    }

    public function update(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $request->validate([
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $member->update(['role' => $request->role]);

        return back()->with('success', 'Member role updated.');
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $member->delete();

        return back()->with('success', 'Member removed.');
    }
}
