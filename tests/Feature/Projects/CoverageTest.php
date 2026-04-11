<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverageTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserAndProject(string $globalRole = 'tester'): array
    {
        $user    = User::factory()->withRole($globalRole)->create();
        $project = Project::factory()->create();
        $project->projectMembers()->create(['user_id' => $user->id, 'role_id' => Role::where('slug', $globalRole)->value('id')]);

        return [$user, $project];
    }

    // ── Coverage page ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.coverage', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_coverage(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.coverage', $project))->assertOk();
    }

    public function test_non_member_cannot_view_coverage(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->tester()->create();
        $this->actingAs($user)->get(route('projects.coverage', $project))->assertForbidden();
    }
}
