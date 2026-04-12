# Project Plan

## Overview

The RTM application is developed across 8 milestones. **v0.1 covers Milestones 1–8 in full**, with a small set of Milestone 7 features deferred to v0.2 (coverage trend chart, sprint progress report, notifications, and PDF/Excel export).

**v0.2** adds configurable role-based permissions (Milestone 9) and the remaining Milestone 7 features (Milestone 10).

---

## Milestone 1 — Foundation & Auth ✅

**Goal:** Establish the application foundation with a working authentication system and role-based access control.

### Features
- User authentication (login, logout, registration, password reset, email verification)
- User roles: Admin, Project Manager, Business Analyst, Developer, Tester, Viewer
- Global role assignment (system-wide)
- Project-level role overrides (a user can have different roles per project)
- User invitation (invite by email, assign role on invite)
- Basic user profile (name, email, password change)
- Admin panel for user management (list, edit roles, deactivate)

### Access Rules
- Only Admins can invite users and manage global roles
- Project Managers can assign project-level roles within their projects

---

## Milestone 2 — Project Management ✅

**Goal:** Allow users to create and manage projects, with per-project dashboards and member management.

### Features
- Projects CRUD (create, view, edit, archive)
- Project status: Active, On Hold, Archived
- Project metadata: name, description, owner, start date, target date
- All-projects dashboard: summary cards per project (status, coverage %, open items)
- Project members: assign users to a project with a role
- Individual project dashboard:
  - Requirements count (BR / TR)
  - Test case coverage summary
  - Open vs. closed items
  - Recent activity feed

### Access Rules
- Admins and Project Managers can create projects
- Only project members can view a project
- Project Managers and above can edit project settings

---

## Milestone 3 — Requirements Management ✅

**Goal:** Manage business and technical requirements with full traceability links between them.

### Features
- **Business Requirements (BR)**
  - CRUD with: title, description, priority (Critical / High / Medium / Low), status, category, tags
  - Status lifecycle: Draft → Review → Approved → Implemented → Deprecated
- **Technical Requirements (TR)**
  - CRUD with: title, description, type (Functional / Non-Functional / Constraint), status
  - Linked to one or more BRs (many-to-many)
- Traceability links: BR ↔ TR
- Requirement versioning (track changes with author and timestamp)
- Comments and activity log per requirement
- Bulk import via CSV

### Access Rules
- Business Analysts and above can create/edit BRs
- Developers and above can create/edit TRs
- Viewers can read only

---

## Milestone 4 — Test Cases & Coverage ✅

**Goal:** Manage test cases linked to technical requirements, track execution, and calculate coverage.

### Features
- **Test Cases**
  - CRUD with: title, description, steps, expected result, type (Manual / Automated), priority
  - Linked to one or more TRs (many-to-many)
  - Assignee field
- **Test Runs**
  - Execute a test case: Pass / Fail / Blocked / Skipped
  - Run history with timestamp and executor
  - Notes per run
- **Coverage Calculation**
  - TR coverage: % of TRs with at least one linked passing test case
  - BR coverage: % of BRs whose linked TRs are fully covered
  - Uncovered items flagged automatically

### Access Rules
- Testers and above can create test cases and log runs
- Viewers can read test cases and results

---

## Milestone 5 — RTM Visualization ✅

**Goal:** Provide interactive views of the full traceability matrix and coverage gaps.

### Features
- **Matrix View:** Accordion-style grid showing BR → TR → Test Case linkages with status and coverage indicators
- **Waffle Grid:** BR × TR and TR × TC cross-reference grids with cell-level coverage state
- **Traceability Graph:** Node-link diagram showing the relationship between BRs, TRs, and test cases
- **Coverage Gap View:** Highlights BRs and TRs with missing or failing coverage
- **Filters:** by status, coverage gap

### Access Rules
- All project members can view the RTM
- Export available to Project Managers and above (deferred to v0.2)

---

## Milestone 6 — Capacity Planning ✅

**Goal:** Enable planning and workload tracking linked to requirements.

### Features
- **Tasks**
  - CRUD with: title, description, effort estimate (story points or hours), status, due date
  - Linked to a BR or TR
  - Assigned to a project member
  - Calendar and Gantt views
- **Sprints / Iterations**
  - Create sprints with start/end dates
  - Assign tasks to sprints
  - Sprint capacity (total estimated effort vs. available capacity per assignee)
- **Workload View**
  - Per-assignee workload across active sprints
  - Overallocation warnings
- **Progress Tracking**
  - Planned vs. actual effort per sprint

### Access Rules
- Project Managers can create and manage sprints
- Developers and Testers can update task status and log actual effort

---

## Milestone 7 — Reporting ✅ (partial — see deferred items)

**Goal:** Provide project health visibility through reports and structured test execution.

### Features delivered in v0.1
- **Project Health Report**
  - Coverage % for BRs and TRs with progress bars
  - Test case latest-run breakdown (Pass / Fail / Blocked / Not Run)
  - Task summary with overdue count
  - Status distribution doughnut charts for BRs, TRs, Test Cases, and Tasks
- **Test Suites**
  - Create a test suite by selecting one or more BRs; all TCs linked via BR → TR are automatically included
  - View test logs per suite: pass-rate bar, BR → TR → TC accordion tree, expandable run history per test case
- **CSV Export**
  - BRs and TRs exportable to CSV

### Deferred to v0.2
- Coverage trend over time chart (requires `coverage_snapshots` table and scheduled snapshot command)
- Sprint progress / burndown report
- In-app notifications (assignment, status change, comment mention)
- Email notifications (assignment, overdue items, configurable per user)
- RTM export to Excel and PDF
- Reports export to PDF

### Access Rules
- All members can view reports for their projects
- Only Admins and Project Managers can export (when implemented)

---

## Milestone 8 — Code Quality & Hardening ✅

**Goal:** Address accumulated technical debt across the codebase to improve maintainability, reliability, performance, and security.

### 8.1 — Form Request Extraction ✅
All validation extracted from controllers into dedicated FormRequest classes under `app/Http/Requests/Projects/`: `BusinessRequirementRequest`, `TechnicalRequirementRequest`, `TaskRequest`, `TestCaseRequest`, `TestRunRequest`, `SprintRequest`, `CommentRequest`. Enum validation standardised using `Rule::in()`.

### 8.2 — Resource/Presenter Layer ✅
Laravel API Resource classes created under `app/Http/Resources/`: `BusinessRequirementResource`, `TechnicalRequirementResource`, `TaskResource`, `TestCaseResource`, `SprintResource`, `ProjectResource`. Inline `->map()` transformation chains replaced with Resource classes.

### 8.3 — Authorization via Policies ✅
Policy classes created for all major resources (`BusinessRequirementPolicy`, `TechnicalRequirementPolicy`, `TaskPolicy`, `TestCasePolicy`, `CommentPolicy`, `SprintPolicy`, `TestSuitePolicy`, `ProjectPolicy`, `UserPolicy`) and registered in `AppServiceProvider`. All controllers use `$this->authorize()`. Cross-project scope validation on link controllers handled via `Rule::exists()->where('project_id', ...)` in FormRequests.

### 8.4 — Activity Logging via Observers ✅
Observers in place for all models requiring activity tracking (`BusinessRequirementObserver`, `TechnicalRequirementObserver`, `TestCaseObserver`, `CommentObserver`, `TaskObserver`, `TaskLogObserver`). No manual `ActivityLog::create()` calls remain in controllers.

### 8.5 — Performance ✅
Performance indexes migration added for `status`, `assignee_id`, and `due_date` columns across tasks, test runs, requirements, test cases, and activity logs. `HasStatusCounts` trait extracted to eliminate repeated status-count query patterns.

### 8.6 — Error Handling ✅
Global exception handler configured in `bootstrap/app.php` using Laravel 12's `withExceptions()`. `AuthorizationException` and `ModelNotFoundException` return graceful Inertia-compatible back-redirects with flash messages on non-GET requests.

### 8.7 — Test Coverage ✅
Feature test suite under `tests/Feature/Projects/` covering all major controllers: `ProjectTest`, `BusinessRequirementTest`, `TechnicalRequirementTest`, `TaskTest`, `TestCaseTest`, `SprintTest`, `CommentTest`, `RtmTest`, `CoverageTest`, `TrLinkTest`, `TcLinkTest`.

### Access Rules
- No user-visible changes; all improvements are internal

---

## Milestone 9 — Configurable Role-Based Permissions

**Goal:** Replace hardcoded role-permission checks with a database-driven permission system that admins can configure. Admins can define custom roles and control exactly what each role can view and do across the application.

---

### 9.1 — Data Model

**New tables:**

`roles` — stores both built-in and custom roles
- `id`, `name`, `is_system` (bool — built-in roles cannot be renamed or deleted), `created_by`, `timestamps`

`permissions` — the fixed set of all available permission keys
- `id`, `key` (e.g. `br.create`), `label`, `group`

`role_permissions` — which permissions each role holds
- `role_id`, `permission_id`

**Schema changes:**
- `users.role` (enum) → `users.role_id` (FK → roles)
- `project_members.role` (enum) → `project_members.role_id` (FK → roles)

---

### 9.2 — Built-in Roles & Seeded Defaults

The 6 existing roles are seeded as system roles (`is_system = true`). The Admin role always bypasses all permission checks (superuser) and cannot be modified.

Default permission matrix:

| Permission | PM | BA | Developer | Tester | Viewer |
|---|---|---|---|---|---|
| br.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| br.create, edit, delete | ✅ | ✅ | | | |
| br.change_status | ✅ | ✅ | | | |
| br.import, export | ✅ | ✅ | | | |
| tr.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| tr.create, edit, delete | ✅ | | ✅ | | |
| tr.change_status, import, export | ✅ | | ✅ | | |
| tc.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| tc.create, edit, delete, import, export | ✅ | | | ✅ | |
| test_runs.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| test_runs.create | ✅ | | | ✅ | |
| test_suites.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| test_suites.create, delete | ✅ | | | ✅ | |
| tasks.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| tasks.create, edit, delete | ✅ | | ✅ | ✅ | |
| tasks.change_status | ✅ | | ✅ | ✅ | |
| sprints.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| sprints.create, edit, delete | ✅ | | | | |
| rtm.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| rtm.export | ✅ | ✅ | | | |
| reports.view | ✅ | ✅ | | ✅ | |
| reports.export | ✅ | ✅ | | | |
| coverage.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| members.view | ✅ | ✅ | ✅ | ✅ | ✅ |
| members.manage | ✅ | | | | |
| projects.create, edit, archive | ✅ | | | | |

---

### 9.3 — Permission Keys (full list)

| Group | Keys |
|---|---|
| Business Requirements | `br.view` `br.create` `br.edit` `br.delete` `br.change_status` `br.import` `br.export` |
| Technical Requirements | `tr.view` `tr.create` `tr.edit` `tr.delete` `tr.change_status` `tr.import` `tr.export` |
| Test Cases | `tc.view` `tc.create` `tc.edit` `tc.delete` `tc.import` `tc.export` |
| Test Runs | `test_runs.view` `test_runs.create` |
| Test Suites | `test_suites.view` `test_suites.create` `test_suites.delete` |
| Tasks | `tasks.view` `tasks.create` `tasks.edit` `tasks.delete` `tasks.change_status` |
| Sprints | `sprints.view` `sprints.create` `sprints.edit` `sprints.delete` |
| RTM | `rtm.view` `rtm.export` |
| Reports | `reports.view` `reports.export` |
| Coverage | `coverage.view` |
| Members | `members.view` `members.manage` |
| Projects | `projects.create` `projects.edit` `projects.archive` |

---

### 9.4 — Permission Resolution

A `hasPermission(string $key): bool` method on the `User` model resolves the user's effective role (project-level override → global role) and checks the cached `role_permissions` set. The permission set per role is cached to avoid repeated DB lookups.

All existing Policy classes replace hardcoded role-name checks with `$user->hasPermission('key')` calls.

---

### 9.5 — Custom Roles

Admins can create custom roles with any name and assign any combination of permissions. Custom roles behave identically to built-in roles and can be assigned to users and project members. Custom roles can be renamed or deleted (deletion blocked if any users are currently assigned).

---

### 9.6 — Admin UI

**Roles list** (`/admin/roles`)
- Table of all roles with member count and system/custom badge
- Create new role button
- Edit name (custom only), delete (custom only, unassigned only)

**Permission matrix** (`/admin/roles/{role}/permissions`)
- Permissions grouped by resource, toggle checkboxes per permission
- Changes saved atomically (replaces the full permission set for the role)
- Admin role shown as read-only (superuser — all permissions always on)

---

### 9.7 — Implementation Sequence

1. Migrations: `roles`, `permissions`, `role_permissions` tables; alter `users.role` and `project_members.role` to FK columns
2. Seeder: seed 6 built-in roles and the full default permission matrix
3. `User::hasPermission()` with role-level cache invalidation on permission change
4. Update all Policy classes to use `hasPermission()`
5. Admin UI: roles CRUD
6. Admin UI: permission matrix per role

### Access Rules
- Only Admins can access role and permission management
- Admins cannot modify the Admin role's permissions or delete system roles

---

## Milestone 10 — Remaining Reporting Features

**Goal:** Deliver the deferred Milestone 7 reporting and notification features.

### Features
- **Coverage trend over time** — daily snapshot of BR and TR coverage %; line chart on the project health report page
- **Sprint progress / burndown report** — planned vs. actual effort chart per sprint
- **In-app notifications** — bell icon with unread count; notify on assignment, status change, and comment mention
- **Email notifications** — assignment and overdue item alerts, configurable per user
- **RTM export** — export the traceability matrix to Excel and PDF
- **Reports export** — export the project health report to PDF

### Access Rules
- All members can view reports and receive notifications for their projects
- Export restricted to roles with `rtm.export` or `reports.export` permission (as configured in Milestone 9)

---

## Milestone 11 — BR Dependency Graph ✅

**Goal:** Allow business requirements to declare blocking relationships with each other, visualise those dependencies as a directed graph, and surface prioritisation guidance based on the dependency chain.

---

### 11.1 — Data Model ✅

**New table: `br_dependencies`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `blocking_br_id` | FK → `business_requirements` | the BR that must be resolved first |
| `blocked_br_id` | FK → `business_requirements` | the BR that cannot proceed until the blocker is done |
| `created_by` | FK → `users` | who created the link |
| `timestamps` | | |

Constraints:
- `UNIQUE (blocking_br_id, blocked_br_id)` — no duplicate links
- Self-link guard enforced in `BrDependencyController`
- Cycle detection enforced in `BrDependencyController` via BFS traversal

---

### 11.2 — Relationship API ✅

Two self-referential `BelongsToMany` relationships on `BusinessRequirement`:
- `blockingBrs()` — BRs that this BR is blocking (outgoing edges, pivot: `blocking_br_id`)
- `blockedByBrs()` — BRs that are blocking this BR (incoming edges, pivot: `blocked_br_id`)

`getIsBlockedAttribute()` — true when the `blockedByBrs` relation is loaded and contains at least one BR not in `implemented` status.

---

### 11.3 — BR Detail Page Changes ✅

On the BR show page, a new **Dependencies** panel:
- **Blocked By** section — searchable picker (search input + select dropdown) to add a prerequisite BR. Each existing blocker shows ref, title, status badge, and a Remove button.
- **Blocks** section — informational list of BRs that depend on this one, each with a Remove button.
- ⚠ **Blocked** badge in the BR header when `is_blocked` is true.
- "View graph →" link in the panel header.

---

### 11.4 — Dependency Graph View ✅

Page: `GET /projects/{project}/requirements/business/graph` → `BrGraph.vue`

Custom SVG DAG (no external library):
- **Nodes:** 240×82px rounded rectangles. Fill/stroke colour encodes status. Amber border = blocked; green border = ready (all blockers implemented). Titles word-wrap up to 2 lines (~30 chars each) using SVG `<tspan>` elements — no truncation.
- **Edges:** cubic bezier curves with SVG arrow markers. Amber = blocker pending; green = blocker implemented.
- **Layout:** longest-path topological layer assignment; nodes distributed vertically within each layer.
- **Unlinked BRs:** shown in a separate table below the graph.
- **Interactions:** hover tooltip (Teleport) showing ref, title, priority, status, and blocked/ready state; click navigates to BR detail.
- **Legend** embedded inside the SVG (below the graph area) explaining status colours and edge/node border states. Being part of the SVG ensures the legend is included in PNG exports.
- **Export PNG** button — client-side only; serializes the SVG (with embedded legend and `#f8fafc` background) to a canvas at 2× resolution and triggers a browser download named `br-dependency-graph-{project-name}.png`. Button is hidden when there are no linked nodes.

---

### 11.5 — BR List Page Changes ✅

- **Dependency Graph** button in the header (links to graph view).
- **Blockers** column showing the count of incoming blockers (amber when > 0, dash when none).
- **Blocked** badge inline with the title on rows where `is_blocked` is true.

---

### 11.6 — Cycle Detection ✅

`BrDependencyController::wouldCreateCycle()` — BFS from `blocked_br_id` following outgoing blocking edges. If `blocking_br_id` is reachable, the link is rejected with a validation error: _"This link would create a circular dependency."_

---

### 11.7 — Seeder ✅

`BrDependencySeeder` seeds two links for the E-Commerce Platform project:
- BR-001 (Auth) blocks BR-003 (Checkout)
- BR-002 (Catalog) blocks BR-003 (Checkout)

This produces a diamond shape in the graph with BR-003 in a "Blocked" state, and BR-004 in the unlinked list.

### Access Rules
- Users with `br.edit` permission can add and remove dependency links
- All project members with `br.view` can see the graph and dependency panels


---

## Milestone 12 — Organisation Membership & Subscription Tiers ✅

**Goal:** Introduce a multi-tenant organisation layer. Each company or organisation owns its projects and users. Organisations subscribe to one of four functional tiers (Basic → Starter → Standard → Pro), and within each tier they choose a seat plan that caps the number of active users. Feature access is enforced across the entire application based on the active subscription.

---

### 12.1 — Data Model ✅

#### New table: `organizations`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | display name |
| `slug` | string unique | URL-safe identifier |
| `owner_id` | FK → `users` | the user who administers the org |
| `is_active` | boolean | soft-disable an org without deleting |
| `timestamps` | | |

#### New table: `subscription_tier_options`

Seeded, not user-editable. Defines every purchasable plan.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tier` | enum(`basic`,`starter`,`standard`,`pro`) | functional tier |
| `seats` | unsignedSmallInt | max active users (e.g. 10, 50, 100, 150, 200) |
| `label` | string | human-readable (e.g. "Starter · 50 seats") |
| `sort_order` | unsignedSmallInt | for UI ordering |

Seeded options (indicative):

| Tier | Seat options |
|---|---|
| Basic | 10, 25 |
| Starter | 50, 100 |
| Standard | 50, 100, 150 |
| Pro | 50, 100, 150, 200 |

#### New table: `organization_subscriptions`

One active row per organisation at any time.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `organization_id` | FK → `organizations` | |
| `tier_option_id` | FK → `subscription_tier_options` | the chosen plan |
| `status` | enum(`trial`,`active`,`expired`,`cancelled`) | |
| `trial_ends_at` | timestamp nullable | set on org creation |
| `starts_at` | timestamp | when this subscription became active |
| `ends_at` | timestamp nullable | null = open-ended |
| `timestamps` | | |

#### Schema additions

- `users.organization_id` FK → `organizations` (nullable during migration; required thereafter)
- `projects.organization_id` FK → `organizations`

---

### 12.2 — Tier Feature Matrix ✅

Each tier unlocks a cumulative set of application features. Enforcement is handled by a `TierGate` service, not by modifying the existing RBAC permission table.

| Feature area | Basic | Starter | Standard | Pro |
|---|---|---|---|---|
| Projects (create, edit, archive) | ✅ | ✅ | ✅ | ✅ |
| Tasks & task management | ✅ | ✅ | ✅ | ✅ |
| Member management | ✅ | ✅ | ✅ | ✅ |
| Business Requirements (BR) | ❌ | ✅ | ✅ | ✅ |
| Technical Requirements (TR) | ❌ | ✅ | ✅ | ✅ |
| BR Dependency Graph | ❌ | ✅ | ✅ | ✅ |
| Test Cases (TC) | ❌ | ❌ | ✅ | ✅ |
| Test Runs & Test Suites | ❌ | ❌ | ✅ | ✅ |
| Sprints | ❌ | ❌ | ✅ | ✅ |
| RTM view & export | ❌ | ❌ | ❌ | ✅ |
| Reports & coverage analytics | ❌ | ❌ | ❌ | ✅ |
| CSV import / export | ❌ | ❌ | ❌ | ✅ |

---

### 12.3 — TierGate Service ✅

`App\Services\TierGate` — the single source of truth for tier enforcement.

```php
TierGate::for($organization)->can('br')      // bool
TierGate::for($organization)->cannot('rtm')  // bool
TierGate::for($organization)->assertCan('tc') // throws HttpException 403 with upgrade prompt
```

Feature keys: `br`, `tr`, `tc`, `test_runs`, `test_suites`, `sprints`, `rtm`, `reports`, `imports_exports`.

The gate reads `$organization->activeSubscription->tierOption->tier` and compares it against a static feature map. It bypasses the check entirely for system admins.

A `CheckTierAccess` middleware wraps controller groups, injecting the resolved organization from the authenticated user.

---

### 12.4 — Seat Limit Enforcement ✅

When inviting a new user (or reactivating an existing one), the system checks:

```
active_users_in_org <= tier_option.seats
```

If the limit is reached, the invite endpoint returns a validation error: _"Your plan allows up to {n} users. Upgrade your seat plan to invite more."_

Active user count = users in the org where `is_active = true`.

---

### 12.5 — Organisation Management (Admin) ✅

System admins can manage all organisations from `/admin/organizations`:

- List: name, owner, tier, seats used / seats total, status, created date
- Create: name, slug, owner (user picker), initial tier option
- Edit: name, owner, active flag
- View detail: subscription history, member list

Org owners (non-admin) cannot access the admin org panel. They access their own org via the subscription settings page (12.6).

---

### 12.6 — Subscription Settings (Org Owner) ✅

Route: `/settings/subscription` — visible to the org owner only.

- Current plan summary: tier name, seat limit, seats used, status, renewal/expiry date
- **Change plan** — pick a different tier or seat option from a grid of available plans; POST submits the change (no payment gateway in this milestone — treat as immediate)
- Tier comparison table showing which features unlock at each tier
- Locked features shown with a padlock icon and "Upgrade to [tier]" label

---

### 12.7 — UI Enforcement (Feature Lock) ✅

Locked navigation items and action buttons show a padlock icon and are disabled (not hidden), with a tooltip explaining which tier unlocks the feature. This gives lower-tier users visibility into what's available at higher tiers.

Controllers guard at the action level using `TierGate::assertCan()`, returning HTTP 403 with an Inertia shared `tier_locked` flash payload that the frontend renders as an upgrade prompt modal.

---

### 12.8 — Migration Strategy ✅

1. Create `organizations` table (`owner_id` nullable to avoid chicken-and-egg on fresh installs).
2. Create `subscription_tier_options` table and seed all plan rows inline in the migration.
3. Create `organization_subscriptions` table.
4. Add `organization_id` (nullable FK) to `users` and `projects` — no backfill in the migration.
5. `OrganizationSeeder` handles all org creation and backfill after users are seeded, so fresh installs (`migrate:fresh --seed`) and existing databases both work correctly.
6. Acme Corp is seeded with a Pro · 100-seat active subscription so no existing dev workflow breaks.

---

### 12.9 — Seeder ✅

`OrganizationSeeder`:
- Creates two orgs: "Acme Corp" (Pro · 100 seats) and "Beta Inc" (Starter · 50 seats)
- Assigns the seeded admin user to Acme Corp as owner
- Assigns the E-Commerce Platform project to Acme Corp
- Seeds the default trial for Beta Inc

---

### 12.10 — Org Owner Identity ✅

Each organisation has exactly one owner (`organizations.owner_id`). The owner account is created automatically when an org is seeded or created via the admin panel.

`User::isOrgOwner()` — returns true when `$this->id === $this->organization->owner_id`.

`User::hasPermission()` is extended so that org owners bypass all RBAC checks within their own organisation's projects (equivalent to PM-level access within the org, without needing a project role). System admins continue to bypass all checks globally.

`is_org_owner` is shared via Inertia middleware so the frontend can conditionally render owner-only controls.

---

### 12.11 — Org User Management (Org Owner) ✅

Route group: `/org/users` — accessible to org owners and system admins only.

- **List** (`GET /org/users`): all users in the org with name, email, role, active status, seat count summary.
- **Invite** (`GET /org/invitations`, `POST /org/invitations`): invite by email, assign a role. Seat limit enforced before sending. Org owners can only invite into their own org.
- **Update role** (`PATCH /org/users/{user}/role`): assign any system or org-scoped custom role to a user.
- **Toggle active** (`PATCH /org/users/{user}/toggle-active`): deactivate/reactivate users (seat count decrements on deactivation).

---

### 12.12 — Org-Scoped Role Management (Org Owner) ✅

Add `organization_id` (nullable FK) to the `roles` table. Roles with `organization_id = null` are system-wide (read-only for org owners). Roles with an `organization_id` are custom roles visible and editable only within that org.

Route group: `/org/roles` — accessible to org owners and system admins.

- **List** (`GET /org/roles`): system roles (read-only) + org-scoped custom roles (editable).
- **Create** (`POST /org/roles`): create a new custom role for the org.
- **Edit name** (`PATCH /org/roles/{role}`): rename a custom org role.
- **Delete** (`DELETE /org/roles/{role}`): delete a custom org role (disallowed if users are assigned to it).
- **Permissions** (`GET /org/roles/{role}/permissions`, `PUT /org/roles/{role}/permissions`): assign permissions to a custom org role.

---

### 12.13 — Default Org Owner Account ✅

When creating a new organisation via the admin panel, the admin chooses one of two modes:

- **Create new account** — enter name + email; the system creates the user with a random temporary password, assigns them Project Manager role, and links them as `organization.owner_id`.
- **Assign existing user** — pick any existing user from a dropdown; their `organization_id` is updated to the new org.

`OrganizationSeeder` creates a dedicated `owner@beta.example.com` account for Beta Inc. Acme Corp uses the seeded system admin as owner.

Users registering via an org invitation (`/register?invitation=…`) are automatically assigned to the invitation's `organization_id`.

Non-admin users that don't belong to an org (e.g. direct registrations without an invitation) have `organization_id = null` and see no org features until assigned.

---

### Access Rules

- System admin can create/manage all organisations, users, roles, and subscriptions globally
- Org owner bypasses RBAC checks within their org's projects; manages org users, invitations, and org-scoped custom roles
- Regular users are subject to their assigned role's permissions (project-level override applies)
- Feature access is org-wide — all users in an org share the same tier gate
- Seat limit is enforced at invite/reactivation time, not at login; owner account counts as one seat

---

---

## Milestone 13 — Kanban Board View for Tasks

**Goal:** Add a Kanban board view for tasks alongside the existing list view, both on the project Tasks page and on individual Sprint pages. Users can visualise work by status at a glance, filter by priority and category, and move tasks across columns to update their status without leaving the board.

---

### 13.1 — View Toggle

Both the project Tasks page (`/projects/{project}/tasks`) and the Sprint detail page (`/projects/{project}/sprints/{sprint}`) gain a **List / Board** toggle in their page header. The selected view is preserved per page in `localStorage` so it persists across navigation.

---

### 13.2 — Kanban Board Layout

The board renders one column per task status in the application's canonical status order:

`Backlog → Todo → In Progress → In Review → Done → Cancelled`

Each column shows:
- Status label as the column header with a count badge
- Scrollable vertical stack of task cards, sorted by priority (Critical → High → Medium → Low)
- A **+ Add Task** affordance at the bottom of the column that opens a quick-create form inline (title, assignee, priority — same project/sprint/status pre-filled)

The board itself is horizontally scrollable when columns overflow the viewport.

---

### 13.3 — Task Card

Each card displays:
- Task title
- Priority badge (colour-coded: Critical = red, High = amber, Medium = blue, Low = slate)
- Category tag (if set)
- Assignee avatar / initials chip
- Due date (red when overdue)

Clicking a card navigates to the task detail page.

---

### 13.4 — Drag-and-Drop Status Update

Tasks can be dragged from one column and dropped into another. On drop:
- The card moves to the target column optimistically in the UI
- A `PATCH /projects/{project}/tasks/{task}` request is fired with `{ status: newStatus }`
- If the request fails, the card snaps back and a toast error is shown

Implementation uses **vue-draggable-next** (wrapper around SortableJS — already a transitive dependency).

Within a column, cards are **not** manually reorderable; priority is the sort key.

---

### 13.5 — Filters

A filter bar above the board (and list) provides:
- **Priority** — multi-select pills: Critical / High / Medium / Low / All
- **Category** — dropdown of all categories present in the current task set, plus "All"

Filters apply reactively (client-side) without a page reload. Active filters are reflected in the URL query string so links can be shared. The same filter bar is used in both list and board views for consistency.

---

### 13.6 — Quick-Add Task

Each column's **+ Add Task** button expands an inline form at the bottom of that column:
- Title (required)
- Assignee (optional — select from project members)
- Priority (optional — defaults to Medium)
- Status is pre-set to the column's status; sprint is pre-set when on the Sprint page

Submitting fires `POST /projects/{project}/tasks` and prepends the new card to the column. The form collapses on success or on Escape.

---

### 13.7 — Backend Changes

No new routes or controllers are required. The existing `TaskController` handles:
- `index` — already returns all tasks with status, priority, category, assignee; no change needed
- `store` — already accepts status; pre-filling column status on the frontend is sufficient
- `update` — already accepts status via `TaskRequest`; drag-and-drop uses this endpoint

The `TaskResource` already includes all fields the card needs (title, status, priority, category, assignee name). No backend changes are anticipated.

---

### 13.8 — Implementation Sequence

1. Install / confirm `vue-draggable-next` is available
2. Build `TaskKanbanBoard.vue` component (columns, cards, drag-and-drop, quick-add)
3. Build `TaskFilters.vue` component (priority pills + category dropdown, URL sync)
4. Update project `Tasks/Index.vue` — add view toggle, wire `TaskKanbanBoard` and `TaskFilters`
5. Update `Sprints/Show.vue` — add view toggle, wire the same components

---

### Access Rules

- All users with `tasks.view` permission can view the Kanban board
- Creating tasks from the board respects the existing `tasks.create` permission gate
- Dragging cards to update status respects the existing `tasks.change_status` permission gate; cards are non-draggable for users without this permission

---

## Delivery Notes

- v0.1 ships Milestones 1–8 as a fully functional RTM application
- v0.2 ships Milestones 9–10: configurable permissions and remaining reporting features
- Milestone 11 extends v0.2 with BR dependency tracking and graph visualisation
- Milestone 12 extends v0.2 with multi-tenant organisation membership and subscription-based feature gating
- Milestone 13 extends v0.2 with a Kanban board view for tasks and sprints
- Auth and roles (Milestone 1) underpin all access rules throughout the application
