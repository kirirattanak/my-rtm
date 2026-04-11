<?php

namespace Database\Seeders;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TrType;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequirementSeeder extends Seeder
{
    public function run(): void
    {
        $ba  = User::where('email', 'ba@rtm.test')->first();
        $dev = User::where('email', 'dev@rtm.test')->first();

        $ecommerce = Project::where('name', 'E-Commerce Platform')->first();

        // Business Requirements for E-Commerce
        $br1 = BusinessRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 1,
            'title'       => 'Users must be able to register and log in securely',
            'description' => 'The system must allow customers to create an account using email/password and support secure login with session management.',
            'priority'    => BrPriority::Critical,
            'status'      => RequirementStatus::Approved,
            'category'    => 'Authentication',
            'tags'        => ['auth', 'security'],
            'created_by'  => $ba->id,
        ]);

        $br2 = BusinessRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 2,
            'title'       => 'Users can browse and search the product catalog',
            'description' => 'Customers should be able to filter products by category, price range, and availability.',
            'priority'    => BrPriority::High,
            'status'      => RequirementStatus::Approved,
            'category'    => 'Catalog',
            'tags'        => ['catalog', 'search'],
            'created_by'  => $ba->id,
        ]);

        $br3 = BusinessRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 3,
            'title'       => 'Checkout process must complete in under 3 steps',
            'description' => 'To reduce cart abandonment, the checkout flow should be streamlined to address, payment, and confirmation.',
            'priority'    => BrPriority::High,
            'status'      => RequirementStatus::Review,
            'category'    => 'Checkout',
            'tags'        => ['ux', 'checkout'],
            'created_by'  => $ba->id,
        ]);

        $br4 = BusinessRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 4,
            'title'       => 'System must support at least 1000 concurrent users',
            'description' => 'During peak sales periods the platform should remain responsive for 1000 simultaneous sessions.',
            'priority'    => BrPriority::Medium,
            'status'      => RequirementStatus::Draft,
            'category'    => 'Performance',
            'tags'        => ['performance', 'scalability'],
            'created_by'  => $ba->id,
        ]);

        // Technical Requirements for E-Commerce
        $tr1 = TechnicalRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 1,
            'title'       => 'Implement JWT-based session authentication',
            'description' => 'Use Laravel Sanctum with SPA token authentication. Tokens must expire after 24 hours.',
            'type'        => TrType::Functional,
            'status'      => RequirementStatus::Approved,
            'created_by'  => $dev->id,
        ]);

        $tr2 = TechnicalRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 2,
            'title'       => 'Password must be hashed using bcrypt with cost factor 12',
            'description' => 'All user passwords must be stored using bcrypt. Plain-text passwords must never be logged or stored.',
            'type'        => TrType::NonFunctional,
            'status'      => RequirementStatus::Approved,
            'created_by'  => $dev->id,
        ]);

        $tr3 = TechnicalRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 3,
            'title'       => 'Product search indexed via Elasticsearch',
            'description' => 'Full-text product search must use Elasticsearch with relevance scoring and faceted filtering support.',
            'type'        => TrType::Functional,
            'status'      => RequirementStatus::Review,
            'created_by'  => $dev->id,
        ]);

        $tr4 = TechnicalRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 4,
            'title'       => 'API response time must not exceed 200ms at P95',
            'description' => 'All API endpoints must respond within 200ms at the 95th percentile under normal load.',
            'type'        => TrType::NonFunctional,
            'status'      => RequirementStatus::Draft,
            'created_by'  => $dev->id,
        ]);

        $tr5 = TechnicalRequirement::create([
            'project_id'  => $ecommerce->id,
            'number'      => 5,
            'title'       => 'GDPR: no PII stored beyond retention period',
            'description' => 'Customer PII must be anonymised or deleted 3 years after the last account activity.',
            'type'        => TrType::Constraint,
            'status'      => RequirementStatus::Draft,
            'created_by'  => $dev->id,
        ]);

        // Link BRs to TRs
        $br1->technicalRequirements()->attach([$tr1->id, $tr2->id]);
        $br2->technicalRequirements()->attach([$tr3->id]);
        $br3->technicalRequirements()->attach([$tr3->id]);
        $br4->technicalRequirements()->attach([$tr4->id]);
    }
}
