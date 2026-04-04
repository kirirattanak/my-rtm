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

export type BreadcrumbItemType = BreadcrumbItem;
