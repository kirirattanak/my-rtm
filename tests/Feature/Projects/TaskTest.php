<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'developer'): array
    {
        $user    = User::factory()->withRole($globalRole)->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', $globalRole)->value('id')]);

        return [$user, $project];
    }

    private function task(Project $project, User $creator): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'title'       => 'A task',
            'status'      => 'todo',
            'priority'    => 'medium',
            'effort_unit' => 'points',
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.tasks.index', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_index(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.tasks.index', $project))->assertOk();
    }

    public function test_non_member_cannot_view_index(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->developer()->create();
        $this->actingAs($user)->get(route('projects.tasks.index', $project))->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_developer_can_create_task(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'title' => 'A task']);
    }

    public function test_viewer_cannot_create_task(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', 'viewer')->value('id')]);

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_requires_title(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), ['status' => 'todo', 'priority' => 'medium', 'effort_unit' => 'points'])
            ->assertSessionHasErrors('title');
    }

    public function test_store_requires_valid_status(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), array_merge($this->validPayload(), ['status' => 'invalid']))
            ->assertSessionHasErrors('status');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_developer_can_update_task(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $task = $this->task($project, $user);

        $this->actingAs($user)
            ->patch(route('projects.tasks.update', [$project, $task]), array_merge($this->validPayload(), ['title' => 'Updated Task']))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Task']);
    }

    // ── Update Status ─────────────────────────────────────────────────────────

    public function test_developer_can_update_task_status(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $task = $this->task($project, $user);

        $this->actingAs($user)
            ->patch(route('projects.tasks.status.update', [$project, $task]), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_project_manager_can_delete_task(): void
    {
        $user    = User::factory()->projectManager()->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', 'project_manager')->value('id')]);
        $task = $this->task($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.tasks.destroy', [$project, $task]))
            ->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_developer_cannot_delete_task(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $task = $this->task($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.tasks.destroy', [$project, $task]))
            ->assertForbidden();
    }
}
