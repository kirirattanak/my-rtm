<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtmTest extends TestCase
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

    // ── RTM page ──────────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $project = Project::factory()->create();
        $this->get(route('projects.rtm', $project))->assertRedirect(route('login'));
    }

    public function test_member_can_view_rtm(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $this->actingAs($user)->get(route('projects.rtm', $project))->assertOk();
    }

    public function test_non_member_cannot_view_rtm(): void
    {
        $project = Project::factory()->create();
        $user    = User::factory()->businessAnalyst()->create();
        $this->actingAs($user)->get(route('projects.rtm', $project))->assertForbidden();
    }
}
