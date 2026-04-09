# Project Plan

## Overview

The RTM application is developed across 7 milestones, each building on the previous. Each milestone delivers a functional, usable increment.

---

## Milestone 1 — Foundation & Auth

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

## Milestone 2 — Project Management

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

## Milestone 3 — Requirements Management

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

## Milestone 4 — Test Cases & Coverage

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

## Milestone 5 — RTM Visualization

**Goal:** Provide interactive views of the full traceability matrix and coverage gaps.

### Features
- **Matrix View:** Grid showing BR → TR → Test Case linkages with status and coverage indicators
- **Filters:** by status, assignee, tag, priority, coverage gap
- **Traceability Graph:** Node-link diagram showing the relationship between BRs, TRs, and test cases
- **Coverage Gap View:** Highlight BRs and TRs with missing or failing coverage
- Export matrix to Excel / PDF

### Access Rules
- All project members can view the RTM
- Export available to Project Managers and above

---

## Milestone 6 — Capacity Planning

**Goal:** Enable planning and workload tracking linked to requirements.

### Features
- **Tasks**
  - CRUD with: title, description, effort estimate (story points or hours), status, due date
  - Linked to a BR or TR
  - Assigned to a project member
- **Sprints / Iterations**
  - Create sprints with start/end dates
  - Assign tasks to sprints
  - Sprint capacity (total estimated effort vs. available capacity per assignee)
- **Workload View**
  - Per-assignee workload across active sprints
  - Overallocation warnings
- **Progress Tracking**
  - Planned vs. actual effort
  - Sprint burndown chart

### Access Rules
- Project Managers can create and manage sprints
- Developers and Testers can update task status and log actual effort

---

## Milestone 7 — Reporting & Notifications

**Goal:** Provide exportable reports and keep team members informed through notifications.

### Features
- **Reports**
  - Project health report: coverage %, open requirements, failing tests, overdue tasks
  - Requirements traceability report (full BR → TR → TC chain)
  - Test execution summary
  - Sprint progress report
- **Charts**
  - Coverage trend over time
  - Requirements status distribution
  - Sprint burndown
- **Notifications**
  - In-app: assignment, status change, comment mention
  - Email: assignment, overdue items (configurable per user)
- **Export**
  - RTM export to Excel and PDF
  - Reports export to PDF

### Access Rules
- All members can view reports for their projects
- Only Admins and Project Managers can export reports

---

## Milestone 8 — Code Quality & Hardening

**Goal:** Address accumulated technical debt across the codebase to improve maintainability, reliability, performance, and security.

---

### 8.1 — Form Request Extraction & Validation Standardization

**Problem:** Validation is duplicated inline across 14+ controllers using repetitive `request->validate()` calls. The pattern `'required|in:' . implode(',', EnumClass::values())` appears 20+ times.

**Tasks:**
- Create Form Request classes for all resource types: `BusinessRequirementRequest`, `TechnicalRequirementRequest`, `TaskRequest`, `TestCaseRequest`, `TestRunRequest`, `SprintRequest`, `CommentRequest`
- Standardize enum validation to use `Rule::in(EnumClass::values())` throughout
- Move shared validation rules into base request classes where appropriate

---

### 8.2 — Resource/Presenter Layer

**Problem:** Data transformation logic is scattered across controllers with nearly identical `->map()` chains. `TaskController::show` and `SprintController::taskData()` are near-duplicates; similar patterns repeat in `ProjectController`, `BusinessRequirementController`, `TestCaseController`, and `TechnicalRequirementController`.

**Tasks:**
- Create Laravel API Resource classes: `BusinessRequirementResource`, `TechnicalRequirementResource`, `TaskResource`, `TestCaseResource`, `SprintResource`
- Replace all inline `->map()` / `->through()` transformation chains with Resource classes
- Ensure consistent response shape across index and show endpoints

---

### 8.3 — Authorization via Policies

**Problem:** Authorization is largely absent or handled with ad-hoc inline checks. `CommentController` has a single manual role check instead of a Policy. Link controllers (`TrLinkController`, `TcLinkController`) do not validate cross-project resource access.

**Tasks:**
- Create Policy classes for all major resources: `BusinessRequirementPolicy`, `TechnicalRequirementPolicy`, `TaskPolicy`, `TestCasePolicy`, `CommentPolicy`, `SprintPolicy`
- Register policies in `AuthServiceProvider`
- Replace all manual `abort(403)` / role checks with `$this->authorize()` calls
- Add cross-project scope validation to link controllers (ensure linked resources belong to the same project)

---

### 8.4 — Activity Logging via Observers

**Problem:** Activity logging is inconsistent — some models use Observers (e.g. `BusinessRequirementObserver`) while others manually create `ActivityLog` records inside controllers (`CommentController`, `TaskLogController`).

**Tasks:**
- Audit all manual `ActivityLog::create()` calls in controllers
- Create or extend Observers for all models that require activity tracking
- Remove manual activity log creation from controllers

---

### 8.5 — Performance: Query Optimization & Database Indexes

**Problem:** Several controllers have N+1 query risks due to nested `.map()` chains doing collection filtering after eager loading (`CoverageController`, `RtmController`, `ProjectController`). Key columns lack database indexes.

**Tasks:**
- Add database indexes on `project_id`, `assignee_id`, `created_by`, and `status` columns across all relevant tables via new migrations
- Refactor `CoverageController`, `RtmController`, and `ProjectController` activity query to use eager loading with conditions or move logic to model scopes
- Add foreign key constraints to `task_links` table for `linkable_id`

---

### 8.6 — Error Handling

**Problem:** Zero try/catch blocks exist in any controller. No structured error responses for edge cases.

**Tasks:**
- Add exception handling to link controllers (`TrLinkController`, `TcLinkController`) for invalid or not-found resource requests
- Register a global exception handler in `app/Exceptions/Handler.php` to return consistent JSON error responses for API-like routes
- Handle `ModelNotFoundException` and `AuthorizationException` with appropriate HTTP responses

---

### 8.7 — Test Coverage

**Problem:** Only ~18% of PHP files are tested. Auth and Settings are the only covered areas. Zero tests exist for the core application controllers (Projects, Requirements, Tasks, Test Cases, Sprints, RTM, Coverage).

**Tasks:**
- Add Feature tests for all CRUD operations on: `BusinessRequirement`, `TechnicalRequirement`, `Task`, `TestCase`, `TestRun`, `Sprint`
- Add Feature tests for traceability link controllers (`TrLinkController`, `TcLinkController`, `BrLinkController`)
- Add Feature tests for `CoverageController` and `RtmController`
- Add Policy unit tests for all new Policy classes
- Add Form Request unit tests for validation rules
- Target minimum 70% feature test coverage for all controllers

### Access Rules
- No user-visible changes; all improvements are internal
- Existing access rules from Milestones 1–7 remain unchanged

---

## Delivery Notes

- Each milestone must be fully functional before the next begins
- Auth and roles (Milestone 1) are the foundation for all access rules in subsequent milestones
- Database schema should be designed with all milestones in mind from the start to avoid costly migrations
