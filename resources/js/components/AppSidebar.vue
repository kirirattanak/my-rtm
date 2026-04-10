<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart2, ClipboardList, Folder, FlaskConical, GitBranch, Mail, PieChart, SquareKanban, Users, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const isAdmin = page.props.auth.user.role === 'admin';
const currentProject = computed(() => page.props.currentProject);

const mainNavItems: NavItem[] = [
    { title: 'Projects', href: '/projects', icon: Folder },
];

const adminNavItems: NavItem[] = [
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Invitations', href: '/admin/invitations', icon: Mail },
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
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
