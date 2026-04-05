<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { type BreadcrumbItem, type Paginator, type SprintListItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    sprints: Paginator<SprintListItem>;
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Sprints', href: '#' },
];

const statusClass = (sprint: SprintListItem) => {
    const today = new Date().toISOString().slice(0, 10);
    if (sprint.end_date < today) return 'bg-slate-100 text-slate-500';
    if (sprint.is_active) return 'bg-emerald-100 text-emerald-700';
    return 'bg-blue-100 text-blue-700';
};

const statusLabel = (sprint: SprintListItem) => {
    const today = new Date().toISOString().slice(0, 10);
    if (sprint.end_date < today) return 'Completed';
    if (sprint.is_active) return 'Active';
    return 'Upcoming';
};
</script>

<template>
    <Head title="Sprints" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Sprints</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage iterations for {{ project.name }}.</p>
                </div>
                <Link v-if="can.create" :href="route('projects.sprints.create', project.id)"
                    class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                    New Sprint
                </Link>
            </div>

            <div v-if="sprints.data.length === 0" class="bg-white border border-slate-200 rounded-xl px-5 py-12 text-center">
                <p class="text-slate-400 text-sm">No sprints yet. Create your first sprint to start planning.</p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="sprint in sprints.data" :key="sprint.id"
                    class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <Link :href="route('projects.sprints.show', [project.id, sprint.id])"
                                    class="font-semibold text-slate-900 hover:text-primary">
                                    {{ sprint.name }}
                                </Link>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass(sprint)">
                                    {{ statusLabel(sprint) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">
                                {{ sprint.start_date }} → {{ sprint.end_date }}
                                <span v-if="sprint.capacity" class="ml-3">Capacity: {{ sprint.capacity }} hrs</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500 flex-shrink-0 ml-4">
                            <span>{{ sprint.tasks_count }} task{{ sprint.tasks_count !== 1 ? 's' : '' }}</span>
                            <Link :href="route('projects.sprints.show', [project.id, sprint.id])"
                                class="text-primary hover:underline font-medium">View →</Link>
                        </div>
                    </div>
                </div>

                <Pagination :meta="sprints.meta" :links="sprints.links" />
            </div>
        </div>
    </AppLayout>
</template>
