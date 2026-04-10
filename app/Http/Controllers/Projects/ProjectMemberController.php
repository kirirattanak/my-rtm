<?php

namespace App\Http\Controllers\Projects;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectMemberRequest;
use App\Http\Requests\Projects\ProjectMemberRoleRequest;
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

    public function store(ProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $data = $request->validated();

        $project->projectMembers()->create([
            'user_id' => $data['user_id'],
            'role'    => $data['role'],
        ]);

        return back()->with('success', 'Member added.');
    }

    public function update(ProjectMemberRoleRequest $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $data = $request->validated();

        $member->update(['role' => $data['role']]);

        return back()->with('success', 'Member role updated.');
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $member->delete();

        return back()->with('success', 'Member removed.');
    }
}
