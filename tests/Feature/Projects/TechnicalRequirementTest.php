<?php

namespace Tests\Feature\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalRequirementTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'developer'): array
    {
        $user    = User::factory()->create(['role' => UserRole::from($globalRole)]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => $globalRole]);

        return [$user, $project];
    }

    private function tr(Project $project, User $creator): TechnicalRequirement
    {
        return TechnicalRequirement::factory()->create([
            'project_id' => $project->id,
            'number'     => 1,
            'created_by' => $creator->id,
        ]);
    }

    private function validPayload(): array
    {
        return ['title' => 'A TR', 'type' => 'functional', 'status' => 'draft'];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.requirements.technical.index', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_index(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.requirements.technical.index', $project))->assertOk();
    }

    public function test_non_member_cannot_view_index(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->developer()->create();
        $this->actingAs($user)->get(route('projects.requirements.technical.index', $project))->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_developer_can_create_tr(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.technical.store', $project), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('technical_requirements', ['project_id' => $project->id, 'title' => 'A TR']);
    }

    public function test_viewer_cannot_create_tr(): void
    {
        $user    = User::factory()->create(['role' => UserRole::Viewer]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'viewer']);

        $this->actingAs($user)
            ->post(route('projects.requirements.technical.store', $project), $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_requires_title(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.technical.store', $project), ['type' => 'functional', 'status' => 'draft'])
            ->assertSessionHasErrors('title');
    }

    public function test_store_requires_valid_type(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.technical.store', $project), ['title' => 'X', 'type' => 'bad', 'status' => 'draft'])
            ->assertSessionHasErrors('type');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_developer_can_update_tr(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->patch(route('projects.requirements.technical.update', [$project, $tr]), [
                'title' => 'Updated TR', 'type' => 'non_functional', 'status' => 'review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('technical_requirements', ['id' => $tr->id, 'title' => 'Updated TR']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_project_manager_can_delete_tr(): void
    {
        $user    = User::factory()->create(['role' => UserRole::ProjectManager]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'project_manager']);
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.requirements.technical.destroy', [$project, $tr]))
            ->assertRedirect();

        $this->assertDatabaseMissing('technical_requirements', ['id' => $tr->id]);
    }

    public function test_developer_cannot_delete_tr(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.requirements.technical.destroy', [$project, $tr]))
            ->assertForbidden();
    }
}
