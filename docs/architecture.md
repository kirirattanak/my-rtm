# Architecture

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend framework | Laravel 11 (PHP) |
| Frontend framework | Vue 3 (Composition API) |
| Server-side rendering bridge | Inertia.js |
| Styling | Tailwind CSS |
| Component library | shadcn/ui (Vue port) |
| Build tool | Vite |
| Database | MySQL or PostgreSQL |
| Queue | Laravel Queue (database driver, upgradeable to Redis) |
| Auth | Laravel Breeze (session-based) |

## Architectural Decisions

### Inertia.js (no separate API)
The app uses Inertia.js, meaning the frontend is driven by Laravel controllers directly — no REST API or separate SPA backend needed. This simplifies development and keeps the codebase unified.

### Role-Based Access Control (RBAC)
Roles are stored in the database. A user has one global role and can have a project-level role override per project. Authorization is enforced via Laravel Policies and Gates.

### Traceability Model
The core data model is a chain:
```
Business Requirement (BR)
    └── Technical Requirement (TR)  [many-to-many with BR]
            └── Test Case (TC)      [many-to-many with TR]
```
Coverage is computed dynamically based on the presence of passing test runs linked to test cases linked to TRs linked to BRs.

## Directory Structure (planned)

```
app/
  Http/
    Controllers/
      Auth/           # Authentication (login, register, password reset)
      Projects/       # Project management
      Requirements/   # BR and TR controllers
      TestCases/      # Test case and run controllers
      Capacity/       # Sprint and task controllers
    Middleware/
  Models/
    User.php
    Project.php
    ProjectMember.php
    BusinessRequirement.php
    TechnicalRequirement.php
    TestCase.php
    TestRun.php
    Sprint.php
    Task.php
  Policies/           # Authorization policies per model

resources/
  js/
    pages/
      auth/           # Login, Register, etc.
      dashboard/      # All-projects and per-project dashboards
      projects/       # Project management pages
      requirements/   # BR and TR pages
      test-cases/     # Test case and run pages
      rtm/            # RTM matrix and graph views
      capacity/       # Sprint and task planning pages
    components/       # Shared UI components
    layouts/          # App shell layouts
    composables/      # Shared Vue composables

docs/                 # Project documentation
```

## Database Schema (high-level)

```
users               id, name, email, password, role, ...
projects            id, name, description, status, owner_id, ...
project_members     id, project_id, user_id, role
business_requirements   id, project_id, title, description, priority, status, ...
technical_requirements  id, project_id, title, description, type, status, ...
br_tr               business_requirement_id, technical_requirement_id  (pivot)
test_cases          id, project_id, title, description, type, priority, assignee_id, ...
tc_tr               test_case_id, technical_requirement_id  (pivot)
test_runs           id, test_case_id, status, notes, executed_by, executed_at
sprints             id, project_id, name, start_date, end_date, ...
tasks               id, sprint_id, project_id, title, effort, status, assignee_id,
                    requirement_type, requirement_id, ...
comments            id, commentable_type, commentable_id, user_id, body, ...
activity_logs       id, loggable_type, loggable_id, user_id, action, changes, ...
```
