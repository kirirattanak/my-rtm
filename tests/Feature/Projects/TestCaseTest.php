<?php

namespace Tests\Feature\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

class TestCaseTest extends BaseTestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'tester'): array
    {
        $user    = User::factory()->create(['role' => UserRole::from($globalRole)]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => $globalRole]);

        return [$user, $project];
    }

    private function tc(Project $project, User $creator): TestCase
    {
        return TestCase::factory()->create([
            'project_id' => $project->id,
            'number'     => 1,
            'created_by' => $creator->id,
        ]);
    }

    private function validPayload(): array
    {
        return ['title' => 'A test case', 'type' => 'manual', 'priority' => 'medium', 'status' => 'draft'];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.test-cases.index', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_index(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.test-cases.index', $project))->assertOk();
    }

    public function test_non_member_cannot_view_index(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->tester()->create();
        $this->actingAs($user)->get(route('projects.test-cases.index', $project))->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_tester_can_create_test_case(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.test-cases.store', $project), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('test_cases', ['project_id' => $project->id, 'title' => 'A test case']);
    }

    public function test_viewer_cannot_create_test_case(): void
    {
        $user    = User::factory()->create(['role' => UserRole::Viewer]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'viewer']);

        $this->actingAs($user)
            ->post(route('projects.test-cases.store', $project), $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_requires_title(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.test-cases.store', $project), ['type' => 'manual', 'priority' => 'medium', 'status' => 'draft'])
            ->assertSessionHasErrors('title');
    }

    public function test_store_requires_valid_type(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.test-cases.store', $project), array_merge($this->validPayload(), ['type' => 'invalid']))
            ->assertSessionHasErrors('type');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_tester_can_update_test_case(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);

        $this->actingAs($user)
            ->patch(route('projects.test-cases.update', [$project, $tc]), [
                'title' => 'Updated TC', 'type' => 'automated', 'priority' => 'high', 'status' => 'review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('test_cases', ['id' => $tc->id, 'title' => 'Updated TC']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_project_manager_can_delete_test_case(): void
    {
        $user    = User::factory()->create(['role' => UserRole::ProjectManager]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'project_manager']);
        $tc = $this->tc($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.test-cases.destroy', [$project, $tc]))
            ->assertRedirect();

        $this->assertDatabaseMissing('test_cases', ['id' => $tc->id]);
    }

    public function test_tester_cannot_delete_test_case(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.test-cases.destroy', [$project, $tc]))
            ->assertForbidden();
    }

    // ── Log Run ───────────────────────────────────────────────────────────────

    public function test_tester_can_log_test_run(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);

        $this->actingAs($user)
            ->post(route('projects.test-cases.runs.store', [$project, $tc]), [
                'status' => 'pass',
                'notes'  => 'All good.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('test_runs', ['test_case_id' => $tc->id, 'status' => 'pass']);
    }

    public function test_log_run_requires_valid_status(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);

        $this->actingAs($user)
            ->post(route('projects.test-cases.runs.store', [$project, $tc]), ['status' => 'invalid'])
            ->assertSessionHasErrors('status');
    }
}
