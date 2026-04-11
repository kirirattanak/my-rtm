<?php

namespace Database\Seeders;

use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Database\Seeder;

class CapacitySeeder extends Seeder
{
    public function run(): void
    {
        $pm     = User::where('email', 'pm@rtm.test')->first();
        $dev    = User::where('email', 'dev@rtm.test')->first();
        $tester = User::where('email', 'tester@rtm.test')->first();
        $ba     = User::where('email', 'ba@rtm.test')->first();

        $ecommerce = Project::where('name', 'E-Commerce Platform')->first();

        $br1 = BusinessRequirement::where('project_id', $ecommerce->id)->where('number', 1)->first();
        $br2 = BusinessRequirement::where('project_id', $ecommerce->id)->where('number', 2)->first();
        $br3 = BusinessRequirement::where('project_id', $ecommerce->id)->where('number', 3)->first();
        $tr1 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 1)->first();
        $tr2 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 2)->first();
        $tr3 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 3)->first();
        $tr4 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 4)->first();

        // ── Sprint 1 — Auth (completed) ────────────────────────────────────
        $sprint1 = Sprint::create([
            'project_id' => $ecommerce->id,
            'name'       => 'Sprint 1 — Authentication',
            'start_date' => '2026-03-10',
            'end_date'   => '2026-03-21',
            'capacity'   => 80,
        ]);

        $t1 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint1->id,
            'title'           => 'Implement Sanctum token authentication',
            'description'     => 'Set up Laravel Sanctum for SPA authentication with 24h token expiry.',
            'effort_estimate' => 8,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-03-14',
            'assignee_id'     => $dev->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-03-13 16:00:00',
        ]);
        $t1->linkedTrs()->sync([$tr1->id]);

        $t2 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint1->id,
            'title'           => 'Enforce bcrypt password hashing',
            'description'     => 'Verify and update password hashing to use bcrypt with cost factor 12.',
            'effort_estimate' => 4,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-03-14',
            'assignee_id'     => $dev->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-03-14 11:00:00',
        ]);
        $t2->linkedTrs()->sync([$tr2->id]);

        $t3 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint1->id,
            'title'           => 'Write auth acceptance criteria',
            'description'     => 'Document detailed acceptance criteria for user registration and login flows.',
            'effort_estimate' => 5,
            'effort_unit'     => EffortUnit::Points,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-03-12',
            'assignee_id'     => $ba->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-03-12 14:00:00',
        ]);
        $t3->linkedBrs()->sync([$br1->id]);

        $t4 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint1->id,
            'title'           => 'QA: auth flow end-to-end testing',
            'description'     => 'Execute manual test cases TC-001 and TC-002 for login flows.',
            'effort_estimate' => 6,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-03-21',
            'assignee_id'     => $tester->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-03-20 17:00:00',
        ]);
        $t4->linkedTrs()->sync([$tr1->id]);

        // Log actual hours for Sprint 1 tasks
        $t1->logs()->create(['logged_by' => $dev->id,    'hours' => 9.5,  'notes' => 'Token refresh logic took longer than expected']);
        $t2->logs()->create(['logged_by' => $dev->id,    'hours' => 3.0,  'notes' => 'Already partially implemented']);
        $t4->logs()->create(['logged_by' => $tester->id, 'hours' => 5.5,  'notes' => 'All test cases passed on second run']);

        // ── Sprint 2 — Search & Catalog (active) ───────────────────────────
        $sprint2 = Sprint::create([
            'project_id' => $ecommerce->id,
            'name'       => 'Sprint 2 — Catalog & Search',
            'start_date' => '2026-03-31',
            'end_date'   => '2026-04-11',
            'capacity'   => 80,
        ]);

        $t5 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint2->id,
            'title'           => 'Set up Elasticsearch index for products',
            'description'     => 'Configure Elasticsearch index mappings and index existing product data.',
            'effort_estimate' => 12,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::InProgress,
            'due_date'        => '2026-04-07',
            'assignee_id'     => $dev->id,
            'created_by'      => $pm->id,
        ]);
        $t5->linkedTrs()->sync([$tr3->id]);

        $t6 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint2->id,
            'title'           => 'Design search UI with filters',
            'description'     => 'Create wireframes and component specs for search results page with faceted filters.',
            'effort_estimate' => 8,
            'effort_unit'     => EffortUnit::Points,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-04-04',
            'assignee_id'     => $ba->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-04-03 15:00:00',
        ]);
        $t6->linkedBrs()->sync([$br2->id]);

        $t7 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint2->id,
            'title'           => 'Map out 3-step checkout flow',
            'description'     => 'Define the checkout steps: address → payment → confirmation.',
            'effort_estimate' => 3,
            'effort_unit'     => EffortUnit::Points,
            'status'          => TaskStatus::Done,
            'due_date'        => '2026-04-04',
            'assignee_id'     => $ba->id,
            'created_by'      => $pm->id,
            'completed_at'    => '2026-04-04 10:00:00',
        ]);
        $t7->linkedBrs()->sync([$br3->id]);

        $t8 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint2->id,
            'title'           => 'Set up k6 load testing environment',
            'description'     => 'Configure k6 and baseline load test scripts for the product listing endpoint.',
            'effort_estimate' => 6,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Todo,
            'due_date'        => '2026-04-11',
            'assignee_id'     => $tester->id,
            'created_by'      => $pm->id,
        ]);
        $t8->linkedTrs()->sync([$tr4->id]);

        // Partial hours logged on active tasks
        $t5->logs()->create(['logged_by' => $dev->id, 'hours' => 6.0, 'notes' => 'Index mappings done, syncing data in progress']);

        // ── Sprint 3 — Upcoming ────────────────────────────────────────────
        $sprint3 = Sprint::create([
            'project_id' => $ecommerce->id,
            'name'       => 'Sprint 3 — Performance & Compliance',
            'start_date' => '2026-04-14',
            'end_date'   => '2026-04-25',
            'capacity'   => 80,
        ]);

        $t9 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint3->id,
            'title'           => 'Run full P95 load test and optimise queries',
            'description'     => 'Execute k6 load test at 50 concurrent users and tune slow queries.',
            'effort_estimate' => 10,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Todo,
            'due_date'        => '2026-04-18',
            'assignee_id'     => $dev->id,
            'created_by'      => $pm->id,
        ]);
        $t9->linkedTrs()->sync([$tr4->id]);

        Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint3->id,
            'title'           => 'GDPR data retention policy review',
            'description'     => 'Work with legal to define retention schedules and implement anonymisation job.',
            'effort_estimate' => 5,
            'effort_unit'     => EffortUnit::Points,
            'status'          => TaskStatus::Todo,
            'due_date'        => '2026-04-22',
            'assignee_id'     => $ba->id,
            'created_by'      => $pm->id,
        ]);

        $t11 = Task::create([
            'project_id'      => $ecommerce->id,
            'sprint_id'       => $sprint3->id,
            'title'           => 'QA: product search regression suite',
            'description'     => 'Execute TC-004 and write two additional search edge-case test cases.',
            'effort_estimate' => 8,
            'effort_unit'     => EffortUnit::Hours,
            'status'          => TaskStatus::Todo,
            'due_date'        => '2026-04-25',
            'assignee_id'     => $tester->id,
            'created_by'      => $pm->id,
        ]);
        $t11->linkedTrs()->sync([$tr3->id]);
    }
}
