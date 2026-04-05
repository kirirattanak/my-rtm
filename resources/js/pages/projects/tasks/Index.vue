<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import TaskStatusSelect from '@/components/TaskStatusSelect.vue';
import { type BreadcrumbItem, type Paginator, type SelectOption, type TaskListItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    tasks: Paginator<TaskListItem>;
    sprints: SelectOption[];
    filters: { status?: string; sprint_id?: string };
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Tasks', href: '#' },
];

const filterStatus   = ref(props.filters.status ?? '');
const filterSprintId = ref(props.filters.sprint_id ?? '');

// Debounce-free: fire on change
watch([filterStatus, filterSprintId], () => {
    router.get(
        route('projects.tasks.index', props.project.id),
        { status: filterStatus.value || undefined, sprint_id: filterSprintId.value || undefined },
        { preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head title="Tasks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 animate-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Tasks</h1>
                    <p class="text-sm text-slate-500 mt-0.5">All tasks for {{ project.name }}.</p>
                </div>
                <Link v-if="can.create" :href="route('projects.tasks.create', project.id)"
                    class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                    New Task
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3">
                <select v-model="filterStatus"
                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">All statuses</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Done</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="filterSprintId"
                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">All sprints</option>
                    <option value="none">No sprint</option>
                    <option v-for="s in sprints" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <span class="text-xs text-slate-400 ml-auto">{{ tasks.meta.total }} tasks</span>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div v-if="tasks.data.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                    No tasks found.
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Title</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-28">Status</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-28">Effort</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-32">Sprint</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-32">Assignee</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-24">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <Link :href="route('projects.tasks.show', [project.id, task.id])"
                                    class="font-medium text-slate-800 hover:text-primary">
                                    {{ task.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <TaskStatusSelect :task="task" :project-id="project.id" />
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <span v-if="task.effort_estimate">{{ task.effort_estimate }} {{ task.effort_unit_short }}</span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <Link v-if="task.sprint" :href="route('projects.sprints.show', [project.id, task.sprint.id])"
                                    class="hover:text-primary">{{ task.sprint.name }}</Link>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ task.assignee?.name ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ task.due_date ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :meta="tasks.meta" :links="tasks.links" class="px-5" />
            </div>
        </div>
    </AppLayout>
</template>
