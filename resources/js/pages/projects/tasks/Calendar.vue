<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CalendarView from '@/components/CalendarView.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface CalendarTask {
    id: number;
    title: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    category: string | null;
    category_label: string | null;
    due_date: string | null;
    assignee: { id: number; name: string } | null;
}

const props = defineProps<{
    project: { id: number; name: string };
    tasks: CalendarTask[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Tasks', href: route('projects.tasks.index', props.project.id) },
    { title: 'Calendar', href: '#' },
];
</script>

<template>
    <Head title="Task Calendar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Task Calendar</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }} · {{ tasks.length }} tasks</p>
                </div>
                <Link :href="route('projects.tasks.index', project.id)"
                    class="text-sm text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg px-3 py-1.5 transition hover:bg-slate-50">
                    ← Task List
                </Link>
            </div>

            <CalendarView :tasks="tasks" :project-id="project.id" />
        </div>
    </AppLayout>
</template>
