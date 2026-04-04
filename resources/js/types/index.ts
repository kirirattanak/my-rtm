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

export type BreadcrumbItemType = BreadcrumbItem;
