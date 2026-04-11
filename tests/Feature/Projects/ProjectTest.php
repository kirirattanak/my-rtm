<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeProjectWithMember(string $role = 'project_manager'): array
    {
        $user    = User::factory()->withRole($role)->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', $role)->value('id')]);

        return [$user, $project];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_projects_index(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_projects_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('projects.index'))->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_project_manager_can_create_project(): void
    {
        $user = User::factory()->projectManager()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'name'   => 'New Project',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'New Project', 'owner_id' => $user->id]);
    }

    public function test_viewer_cannot_create_project(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), ['name' => 'New Project', 'status' => 'active'])
            ->assertForbidden();
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->projectManager()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), ['status' => 'active'])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_valid_status(): void
    {
        $user = User::factory()->projectManager()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), ['name' => 'X', 'status' => 'invalid'])
            ->assertSessionHasErrors('status');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_project_member_can_view_project(): void
    {
        [$user, $project] = $this->makeProjectWithMember();

        $this->actingAs($user)->get(route('projects.show', $project))->assertOk();
    }

    public function test_non_member_cannot_view_project(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->create();

        $this->actingAs($user)->get(route('projects.show', $project))->assertForbidden();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_project_manager_can_update_project(): void
    {
        [$user, $project] = $this->makeProjectWithMember();

        $this->actingAs($user)
            ->patch(route('projects.update', $project), [
                'name'   => 'Updated Name',
                'status' => 'on_hold',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Name']);
    }

    public function test_viewer_member_cannot_update_project(): void
    {
        [$pm, $project] = $this->makeProjectWithMember();
        $viewer = User::factory()->create();
        $project->projectMembers()->create(['user_id' => $viewer->id, 'role_id' => Role::where('slug', 'viewer')->value('id')]);

        $this->actingAs($viewer)
            ->patch(route('projects.update', $project), ['name' => 'X', 'status' => 'active'])
            ->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_project(): void
    {
        $admin   = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_manager_cannot_delete_project(): void
    {
        [$pm, $project] = $this->makeProjectWithMember();

        $this->actingAs($pm)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();
    }
}
