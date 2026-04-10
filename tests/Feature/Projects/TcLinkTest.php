<?php

namespace Tests\Feature\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

class TcLinkTest extends BaseTestCase
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

    private function tr(Project $project, User $creator): TechnicalRequirement
    {
        return TechnicalRequirement::factory()->create([
            'project_id' => $project->id,
            'number'     => 1,
            'created_by' => $creator->id,
        ]);
    }

    // ── Store (link) ──────────────────────────────────────────────────────────

    public function test_tester_can_link_tr_to_tc(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->post(route('projects.test-cases.tr-links.store', [$project, $tc]), [
                'technical_requirement_id' => $tr->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tr_test_case', [
            'test_case_id'             => $tc->id,
            'technical_requirement_id' => $tr->id,
        ]);
    }

    public function test_viewer_cannot_link_tr_to_tc(): void
    {
        $user    = User::factory()->create(['role' => UserRole::Viewer]);
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role' => 'viewer']);

        $owner = User::factory()->tester()->create();
        $tc    = TestCase::factory()->create(['project_id' => $project->id, 'number' => 1, 'created_by' => $owner->id]);
        $tr    = TechnicalRequirement::factory()->create(['project_id' => $project->id, 'number' => 1, 'created_by' => $owner->id]);

        $this->actingAs($user)
            ->post(route('projects.test-cases.tr-links.store', [$project, $tc]), [
                'technical_requirement_id' => $tr->id,
            ])
            ->assertForbidden();
    }

    public function test_link_requires_tr_from_same_project(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);

        $otherProject = Project::factory()->create();
        $otherOwner   = User::factory()->create();
        $otherTr      = TechnicalRequirement::factory()->create([
            'project_id' => $otherProject->id,
            'number'     => 1,
            'created_by' => $otherOwner->id,
        ]);

        $this->actingAs($user)
            ->post(route('projects.test-cases.tr-links.store', [$project, $tc]), [
                'technical_requirement_id' => $otherTr->id,
            ])
            ->assertSessionHasErrors('technical_requirement_id');
    }

    // ── Destroy (unlink) ──────────────────────────────────────────────────────

    public function test_tester_can_unlink_tr_from_tc(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tc = $this->tc($project, $user);
        $tr = $this->tr($project, $user);
        $tc->technicalRequirements()->attach($tr->id);

        $this->actingAs($user)
            ->delete(route('projects.test-cases.tr-links.destroy', [$project, $tc, $tr]))
            ->assertRedirect();

        $this->assertDatabaseMissing('tr_test_case', [
            'test_case_id'             => $tc->id,
            'technical_requirement_id' => $tr->id,
        ]);
    }
}
