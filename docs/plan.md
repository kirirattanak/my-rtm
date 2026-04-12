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

## Delivery Notes

- v0.1 ships Milestones 1–8 as a fully functional RTM application
- v0.2 ships Milestones 9–10: configurable permissions and remaining reporting features
- Milestone 11 extends v0.2 with BR dependency tracking and graph visualisation
- Auth and roles (Milestone 1) underpin all access rules throughout the application
