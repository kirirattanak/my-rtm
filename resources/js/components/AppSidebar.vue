<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart2, Building2, ClipboardList, Folder, FlaskConical, GitBranch, Mail, PieChart, Shield, SquareKanban, TestTube2, Users, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const isAdmin = page.props.auth.is_admin;
const isOrgOwner = page.props.auth.is_org_owner;
const currentProject = computed(() => page.props.currentProject);

const mainNavItems: NavItem[] = [
    { title: 'Projects', href: '/projects', icon: Folder },
];

const adminNavItems: NavItem[] = [
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Invitations', href: '/admin/invitations', icon: Mail },
    { title: 'Roles & Permissions', href: '/admin/roles', icon: Shield },
    { title: 'Organisations', href: '/admin/organizations', icon: Building2 },
];

const orgNavItems: NavItem[] = [
    { title: 'Users', href: '/org/users', icon: Users },
    { title: 'Roles & Permissions', href: '/org/roles', icon: Shield },
];

const projectNavItems = computed<NavItem[]>(() => {
    const project = currentProject.value;
    if (!project) return [];
    return [
        {
            title: 'Business Requirements',
            href: `/projects/${project.id}/requirements/business`,
            icon: ClipboardList,
        },
        {
            title: 'Technical Requirements',
            href: `/projects/${project.id}/requirements/technical`,
            icon: ClipboardList,
        },
        {
            title: 'Test Cases',
            href: `/projects/${project.id}/test-cases`,
            icon: FlaskConical,
        },
        {
            title: 'Coverage',
            href: `/projects/${project.id}/coverage`,
            icon: PieChart,
        },
        {
            title: 'RTM',
            href: `/projects/${project.id}/rtm`,
            icon: GitBranch,
        },
        {
            title: 'Sprints',
            href: `/projects/${project.id}/sprints`,
            icon: Zap,
        },
        {
            title: 'Tasks',
            href: `/projects/${project.id}/tasks`,
            icon: SquareKanban,
        },
        {
            title: 'Test Suites',
            href: `/projects/${project.id}/test-suites`,
            icon: TestTube2,
        },
        {
            title: 'Reports',
            href: `/projects/${project.id}/reports`,
            icon: BarChart2,
        },
    ];
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="currentProject && projectNavItems.length"
                :items="projectNavItems"
                :label="currentProject.name"
            />
            <NavMain v-if="isOrgOwner && !isAdmin" :items="orgNavItems" label="Organisation" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
