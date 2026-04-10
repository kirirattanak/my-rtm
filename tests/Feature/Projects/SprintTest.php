<?php

namespace Tests\Feature\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SprintTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'project_manager'): array
    {
        $user    = User::factory()->create(['role' => UserRole::from($globalRole)]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => $globalRole]);

        return [$user, $project];
    }

    private function sprint(Project $project): Sprint
    {
        return Sprint::factory()->create(['project_id' => $project->id]);
    }

    private function validPayload(): array
    {
        return [
            'name'       => 'Sprint 1',
            'start_date' => '2026-05-01',
            'end_date'   => '2026-05-14',
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.sprints.index', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_sprint_index(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.sprints.index', $project))->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_project_manager_can_create_sprint(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.sprints.store', $project), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('sprints', ['project_id' => $project->id, 'name' => 'Sprint 1']);
    }

    public function test_developer_cannot_create_sprint(): void
    {
        $user    = User::factory()->developer()->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'developer']);

        $this->actingAs($user)
            ->post(route('projects.sprints.store', $project), $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_requires_name(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.sprints.store', $project), ['start_date' => '2026-05-01', 'end_date' => '2026-05-14'])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_end_date_after_start_date(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.sprints.store', $project), [
                'name'       => 'Bad Sprint',
                'start_date' => '2026-05-14',
                'end_date'   => '2026-05-01',
            ])
            ->assertSessionHasErrors('end_date');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_project_manager_can_update_sprint(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $sprint = $this->sprint($project);

        $this->actingAs($user)
            ->patch(route('projects.sprints.update', [$project, $sprint]), array_merge($this->validPayload(), ['name' => 'Sprint 2']))
            ->assertRedirect();

        $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'name' => 'Sprint 2']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_project_manager_can_delete_sprint(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $sprint = $this->sprint($project);

        $this->actingAs($user)
            ->delete(route('projects.sprints.destroy', [$project, $sprint]))
            ->assertRedirect();

        $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    }
}
