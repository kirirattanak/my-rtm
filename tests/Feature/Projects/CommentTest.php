<?php

namespace Tests\Feature\Projects;

use App\Models\Role;
use App\Models\BusinessRequirement;
use App\Models\Comment;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
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

    // ── Comment on BR ─────────────────────────────────────────────────────────

    public function test_member_can_comment_on_br(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.comments.store', [$project, $br]), [
                'body' => 'A comment.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => BusinessRequirement::class,
            'commentable_id'   => $br->id,
            'user_id'          => $user->id,
            'body'             => 'A comment.',
        ]);
    }

    public function test_comment_on_br_requires_body(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br = $this->br($project, $user);

        $this->actingAs($user)
            ->post(route('projects.requirements.business.comments.store', [$project, $br]), [])
            ->assertSessionHasErrors('body');
    }

    // ── Comment on TR ─────────────────────────────────────────────────────────

    public function test_member_can_comment_on_tr(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $tr = $this->tr($project, $user);

        $this->actingAs($user)
            ->post(route('projects.requirements.technical.comments.store', [$project, $tr]), [
                'body' => 'TR comment.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => TechnicalRequirement::class,
            'commentable_id'   => $tr->id,
            'body'             => 'TR comment.',
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_own_comment(): void
    {
        [$user, $project] = $this->makeUserAndProject();
        $br      = $this->br($project, $user);
        $comment = $br->comments()->create(['user_id' => $user->id, 'body' => 'Mine.']);

        $this->actingAs($user)
            ->delete(route('projects.requirements.comments.destroy', [$project, $comment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        [$owner, $project] = $this->makeUserAndProject();
        $br      = $this->br($project, $owner);
        $comment = $br->comments()->create(['user_id' => $owner->id, 'body' => 'Theirs.']);

        $other = User::factory()->businessAnalyst()->create();
        $project->projectMembers()->create(['user_id' => $other->id, 'role_id' => Role::where('slug', 'business_analyst')->value('id')]);

        $this->actingAs($other)
            ->delete(route('projects.requirements.comments.destroy', [$project, $comment]))
            ->assertForbidden();
    }
}
