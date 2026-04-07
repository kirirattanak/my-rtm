import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    currentProject: { id: number; name: string } | null;
    flash: { success: string | null; error: string | null };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export type UserRole =
    | 'admin'
    | 'project_manager'
    | 'business_analyst'
    | 'developer'
    | 'tester'
    | 'viewer';

export const USER_ROLE_LABELS: Record<UserRole, string> = {
    admin:            'Admin',
    project_manager:  'Project Manager',
    business_analyst: 'Business Analyst',
    developer:        'Developer',
    tester:           'Tester',
    viewer:           'Viewer',
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: UserRole;
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Invitation {
    id: number;
    email: string;
    role: UserRole;
    role_label: string;
    invited_by: string;
    status: 'pending' | 'accepted' | 'expired';
    expires_at: string;
    created_at: string;
}

export type ProjectStatus = 'active' | 'on_hold' | 'archived';

export interface ProjectMember {
    id: number;
    user_id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
}

export interface Project {
    id: number;
    name: string;
    description: string | null;
    status: ProjectStatus;
    status_label: string;
    status_color: string;
    owner: string;
    start_date: string | null;
    target_date: string | null;
    created_at: string;
    members_count?: number;
    members?: ProjectMember[];
}

export type BrPriority = 'critical' | 'high' | 'medium' | 'low';
export type TrType = 'functional' | 'non_functional' | 'constraint';
export type RequirementStatus = 'draft' | 'review' | 'approved' | 'implemented' | 'deprecated';

export interface SelectOption {
    value: string;
    label: string;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Comment {
    id: number;
    body: string;
    user: Pick<User, 'id' | 'name'>;
    created_at: string;
}

export interface BrListItem {
    id: number;
    ref: string;
    number: number;
    title: string;
    priority: BrPriority;
    priority_label: string;
    priority_color: string;
    status: RequirementStatus;
    status_label: string;
    status_color: string;
    category: string | null;
    creator: Pick<User, 'id' | 'name'>;
    tr_count: number;
    created_at: string;
}

export interface TrListItem {
    id: number;
    ref: string;
    number: number;
    title: string;
    type: TrType;
    type_label: string;
    status: RequirementStatus;
    status_label: string;
    status_color: string;
    creator: Pick<User, 'id' | 'name'>;
    br_count: number;
    created_at: string;
}

export interface BusinessRequirement extends BrListItem {
    description: string | null;
    tags: string[];
    updated_at: string;
    technical_requirements: {
        id: number;
        ref: string;
        title: string;
        status: RequirementStatus;
        status_label: string;
        status_color: string;
        type: TrType;
        type_label: string;
    }[];
    comments: Comment[];
}

export interface TechnicalRequirement extends TrListItem {
    description: string | null;
    updated_at: string;
    test_cases: {
        id: number;
        ref: string;
        title: string;
        type: TrType;
        type_label: string;
        priority: BrPriority;
        priority_label: string;
        status: RequirementStatus;
        status_label: string;
        status_color: string;
        assignee: Pick<User, 'id' | 'name'> | null;
        latest_run: TestRunStatus | null;
    }[];
    business_requirements: {
        id: number;
        ref: string;
        title: string;
        status: RequirementStatus;
        status_label: string;
        status_color: string;
        priority: BrPriority;
        priority_label: string;
        priority_color: string;
    }[];
    comments: Comment[];
}

export type TestCaseType = 'manual' | 'automated';
export type TestRunStatus = 'pass' | 'fail' | 'blocked' | 'skipped';
export type TestCaseStatus = RequirementStatus;

export interface TestRun {
    id: number;
    status: TestRunStatus;
    status_label: string;
    color: string;
    notes: string | null;
    executor: Pick<User, 'id' | 'name'>;
    created_at: string;
}

export interface TestCaseListItem {
    id: number;
    ref: string;
    number: number;
    title: string;
    type: TestCaseType;
    type_label: string;
    priority: BrPriority;
    priority_label: string;
    status: RequirementStatus;
    status_label: string;
    status_color: string;
    assignee: Pick<User, 'id' | 'name'> | null;
    creator: Pick<User, 'id' | 'name'>;
    runs_count: number;
    latest_run: TestRunStatus | null;
    created_at: string;
}

export interface TestCase extends TestCaseListItem {
    description: string | null;
    steps: string[];
    expected_result: string | null;
    updated_at: string;
    technical_requirements: {
        id: number;
        ref: string;
        title: string;
        status: RequirementStatus;
        status_label: string;
        status_color: string;
    }[];
    runs: TestRun[];
}

export interface CoverageSummary {
    tr_coverage: number;
    covered_trs: number;
    total_trs: number;
    br_coverage: number;
    covered_brs: number;
    total_brs: number;
}

export interface ProjectCoverage {
    br_count: number;
    tr_count: number;
    tc_count: number;
    covered_brs: number;
    covered_trs: number;
    br_coverage: number;
    tr_coverage: number;
}

export interface ActivityItem {
    id: number;
    action: 'created' | 'updated' | 'deleted' | 'commented' | 'logged_hours';
    subject_type: 'BR' | 'TR' | 'TC' | 'Task' | '?';
    subject_id: number;
    subject_title: string | null;
    path: string;
    user_name: string;
    created_at: string;
}

export interface RtmTestCase {
    id: number;
    ref: string;
    title: string;
    status: RequirementStatus;
    status_label: string;
    latest_run: TestRunStatus | null;
    is_passing: boolean;
}

export interface RtmTr {
    id: number;
    ref: string;
    title: string;
    type: TrType;
    type_label: string;
    status: RequirementStatus;
    status_label: string;
    is_covered: boolean;
    test_cases: RtmTestCase[];
}

export interface RtmBr {
    id: number;
    ref: string;
    title: string;
    priority: BrPriority;
    priority_label: string;
    status: RequirementStatus;
    status_label: string;
    is_covered: boolean;
    trs: RtmTr[];
}

export type TaskStatus = 'todo' | 'in_progress' | 'done' | 'cancelled';
export type EffortUnit = 'points' | 'hours';

export interface TaskLog {
    id: number;
    hours: number;
    notes: string | null;
    logger_name: string;
    created_at: string;
}

export interface TaskListItem {
    id: number;
    title: string;
    status: TaskStatus;
    status_label: string;
    priority: BrPriority;
    priority_label: string;
    category: string | null;
    category_label: string | null;
    effort_estimate: number | null;
    effort_unit: EffortUnit;
    effort_unit_short: string;
    due_date: string | null;
    start_date: string | null;
    end_date: string | null;
    assignee: { id: number; name: string } | null;
    sprint: { id: number; name: string } | null;
}

export interface TaskLink {
    id: number;
    ref: string;
    title: string;
}

export interface Task extends TaskListItem {
    description: string | null;
    effort_unit_label: string;
    creator: Pick<User, 'id' | 'name'>;
    created_at: string;
    completed_at: string | null;
    logged_hours: number;
    effective_actual: number | null;
    linked_brs: TaskLink[];
    linked_trs: TaskLink[];
    linked_tcs: TaskLink[];
    logs: TaskLog[];
}

export interface SprintListItem {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    capacity: number | null;
    is_active: boolean;
    tasks_count: number;
}

export interface WorkloadEntry {
    assignee_id: number;
    assignee_name: string;
    points: { planned: number; done: number };
    hours: { planned: number; actual: number; done: number };
}

export interface BurndownData {
    labels: string[];
    ideal: number[];
    actual: number[];
    total_planned: number;
}

export interface Sprint extends SprintListItem {
    tasks: TaskListItem[];
}

export type BreadcrumbItemType = BreadcrumbItem;
