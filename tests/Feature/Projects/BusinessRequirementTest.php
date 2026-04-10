<?php

namespace Tests\Feature\Projects;

use App\Enums\UserRole;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRequirementTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'business_analyst'): array
    {
        $user    = User::factory()->create(['role' => UserRole::from($globalRole)]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => $globalRole]);

        return [$user, $project];
    }

    private function br(Project $project, User $creator): BusinessRequirement
    {
        return BusinessRequirement::factory()->create([
            'project_id'  => $project->id,
            'number'      => 1,
            'created_by'  => $creator->id,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'title'    => 'A requirement',
            'priority' => 'medium',
            'status'   => 'draft',
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.requirements.business.index', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_index(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.requirements.business.index', $project))->assertOk();
    }

    public function test_non_member_cannot_view_index(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->businessAnalyst()->create();

        $this->actingAs($user)->get(route('projects.requirements.business.index', $project))->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_business_analyst_can_create_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.business.store', $project), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('business_requirements', [
            'project_id' => $project->id,
            'title'      => 'A requirement',
        ]);
    }

    public function test_viewer_cannot_create_br(): void
    {
        $user    = User::factory()->create(['role' => UserRole::Viewer]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'viewer']);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.store', $project), $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_requires_title(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.business.store', $project), ['priority' => 'medium', 'status' => 'draft'])
            ->assertSessionHasErrors('title');
    }

    public function test_store_requires_valid_priority(): void
    {
        [$user, $project] = $this->makeUserAndProject();

        $this->actingAs($user)
            ->post(route('projects.requirements.business.store', $project), ['title' => 'X', 'priority' => 'invalid', 'status' => 'draft'])
            ->assertSessionHasErrors('priority');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_business_analyst_can_update_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);

        $this->actingAs($user)
            ->patch(route('projects.requirements.business.update', [$project, $br]), [
                'title'    => 'Updated Title',
                'priority' => 'high',
                'status'   => 'review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('business_requirements', ['id' => $br->id, 'title' => 'Updated Title']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_project_manager_can_delete_br(): void
    {
        $user    = User::factory()->create(['role' => UserRole::ProjectManager]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'project_manager']);
        $br = $this->br($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.requirements.business.destroy', [$project, $br]))
            ->assertRedirect();

        $this->assertDatabaseMissing('business_requirements', ['id' => $br->id]);
    }

    public function test_business_analyst_cannot_delete_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);

        $this->actingAs($user)
            ->delete(route('projects.requirements.business.destroy', [$project, $br]))
            ->assertForbidden();
    }
}
