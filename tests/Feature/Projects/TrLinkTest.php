<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrLinkTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'business_analyst'): array
    {
        $user    = User::factory()->withRole($globalRole)->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', $globalRole)->value('id')]);

        return [$user, $project];
    }

    private function br(Project $project, User $creator): BusinessRequirement
    {
        return BusinessRequirement::factory()->create([
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

    public function test_business_analyst_can_link_tr_to_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.tr-links.store', [$project, $br]), [
                'technical_requirement_id' => $tr->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('br_tr', [
            'business_requirement_id'  => $br->id,
            'technical_requirement_id' => $tr->id,
        ]);
    }

    public function test_viewer_cannot_link_tr_to_br(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', 'viewer')->value('id')]);

        $owner = User::factory()->businessAnalyst()->create();
        $br    = BusinessRequirement::factory()->create(['project_id' => $project->id, 'number' => 1, 'created_by' => $owner->id]);
        $tr    = TechnicalRequirement::factory()->create(['project_id' => $project->id, 'number' => 1, 'created_by' => $owner->id]);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.tr-links.store', [$project, $br]), [
                'technical_requirement_id' => $tr->id,
            ])
            ->assertForbidden();
    }

    public function test_link_requires_tr_from_same_project(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);

        $otherProject = Project::factory()->create();
        $otherOwner   = User::factory()->create();
        $otherTr      = TechnicalRequirement::factory()->create([
            'project_id' => $otherProject->id,
            'number'     => 1,
            'created_by' => $otherOwner->id,
        ]);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.tr-links.store', [$project, $br]), [
                'technical_requirement_id' => $otherTr->id,
            ])
            ->assertSessionHasErrors('technical_requirement_id');
    }

    // ── Destroy (unlink) ──────────────────────────────────────────────────────

    public function test_business_analyst_can_unlink_tr_from_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);
        $tr = $this->tr($project, $user);
        $br->technicalRequirements()->attach($tr->id);

        $this->actingAs($user)
            ->delete(route('projects.requirements.business.tr-links.destroy', [$project, $br, $tr]))
            ->assertRedirect();

        $this->assertDatabaseMissing('br_tr', [
            'business_requirement_id'  => $br->id,
            'technical_requirement_id' => $tr->id,
        ]);
    }
}
