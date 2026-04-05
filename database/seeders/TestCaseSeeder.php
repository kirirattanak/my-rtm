<?php

namespace Database\Seeders;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use App\Enums\TestRunStatus;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestCaseSeeder extends Seeder
{
    public function run(): void
    {
        $tester = User::where('email', 'tester@rtm.test')->first();
        $dev    = User::where('email', 'dev@rtm.test')->first();

        $ecommerce = Project::where('name', 'E-Commerce Platform')->first();

        $tr1 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 1)->first();
        $tr2 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 2)->first();
        $tr3 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 3)->first();
        $tr4 = TechnicalRequirement::where('project_id', $ecommerce->id)->where('number', 4)->first();

        // TC-001: Login happy path
        $tc1 = TestCase::create([
            'project_id'      => $ecommerce->id,
            'number'          => 1,
            'title'           => 'Successful login with valid credentials',
            'description'     => 'Verify that a registered user can log in with correct email and password.',
            'steps'           => [
                'Navigate to /login',
                'Enter a valid email address',
                'Enter the correct password',
                'Click the Login button',
            ],
            'expected_result' => 'User is redirected to the dashboard. Session is established.',
            'type'            => TestCaseType::Manual,
            'priority'        => BrPriority::Critical,
            'status'          => RequirementStatus::Approved,
            'assignee_id'     => $tester->id,
            'created_by'      => $tester->id,
        ]);
        $tc1->technicalRequirements()->attach([$tr1->id]);

        // TC-002: Login with wrong password
        $tc2 = TestCase::create([
            'project_id'      => $ecommerce->id,
            'number'          => 2,
            'title'           => 'Login fails with incorrect password',
            'description'     => 'Ensure the system rejects login with a wrong password and shows an error.',
            'steps'           => [
                'Navigate to /login',
                'Enter a valid email address',
                'Enter an incorrect password',
                'Click the Login button',
            ],
            'expected_result' => 'Login is rejected. Error message "These credentials do not match our records." is displayed.',
            'type'            => TestCaseType::Manual,
            'priority'        => BrPriority::High,
            'status'          => RequirementStatus::Approved,
            'assignee_id'     => $tester->id,
            'created_by'      => $tester->id,
        ]);
        $tc2->technicalRequirements()->attach([$tr1->id]);

        // TC-003: Password hashing verification
        $tc3 = TestCase::create([
            'project_id'      => $ecommerce->id,
            'number'          => 3,
            'title'           => 'Stored password is bcrypt hashed',
            'description'     => 'Verify that user passwords are never stored in plain text.',
            'steps'           => [
                'Register a new user with password "TestPass123!"',
                'Query the users table for the new user record',
                'Inspect the password column value',
            ],
            'expected_result' => 'Password column contains a bcrypt hash starting with $2y$. No plain text visible.',
            'type'            => TestCaseType::Automated,
            'priority'        => BrPriority::Critical,
            'status'          => RequirementStatus::Approved,
            'assignee_id'     => $dev->id,
            'created_by'      => $dev->id,
        ]);
        $tc3->technicalRequirements()->attach([$tr2->id]);

        // TC-004: Product search returns results
        $tc4 = TestCase::create([
            'project_id'      => $ecommerce->id,
            'number'          => 4,
            'title'           => 'Product search returns relevant results',
            'description'     => 'Verify that searching for a product name returns matching items.',
            'steps'           => [
                'Navigate to the product catalog',
                'Type "laptop" in the search box',
                'Press Enter or click the search button',
            ],
            'expected_result' => 'Results page shows products with "laptop" in the title or description, ordered by relevance.',
            'type'            => TestCaseType::Manual,
            'priority'        => BrPriority::High,
            'status'          => RequirementStatus::Review,
            'assignee_id'     => $tester->id,
            'created_by'      => $tester->id,
        ]);
        $tc4->technicalRequirements()->attach([$tr3->id]);

        // TC-005: API response time
        $tc5 = TestCase::create([
            'project_id'      => $ecommerce->id,
            'number'          => 5,
            'title'           => 'Product listing API responds within 200ms',
            'description'     => 'Load test the product listing endpoint to verify P95 latency.',
            'steps'           => [
                'Run k6 load test against GET /api/products with 50 concurrent users',
                'Collect P95 response time over 60 seconds',
            ],
            'expected_result' => 'P95 response time is under 200ms. No 5xx errors.',
            'type'            => TestCaseType::Automated,
            'priority'        => BrPriority::Medium,
            'status'          => RequirementStatus::Draft,
            'assignee_id'     => $dev->id,
            'created_by'      => $dev->id,
        ]);
        $tc5->technicalRequirements()->attach([$tr4->id]);

        // Log some runs
        $tc1->runs()->create(['executed_by' => $tester->id, 'status' => TestRunStatus::Pass, 'notes' => 'Tested on Chrome 120']);
        $tc2->runs()->create(['executed_by' => $tester->id, 'status' => TestRunStatus::Pass, 'notes' => null]);
        $tc3->runs()->create(['executed_by' => $dev->id, 'status' => TestRunStatus::Pass, 'notes' => 'PHPUnit test passing']);
        $tc4->runs()->create(['executed_by' => $tester->id, 'status' => TestRunStatus::Fail, 'notes' => 'Elasticsearch not returning sorted results']);
        $tc5->runs()->create(['executed_by' => $dev->id, 'status' => TestRunStatus::Blocked, 'notes' => 'Load testing environment not ready']);
    }
}
