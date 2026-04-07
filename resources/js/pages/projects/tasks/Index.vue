<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import TaskStatusSelect from '@/components/TaskStatusSelect.vue';
import { type BreadcrumbItem, type Paginator, type SelectOption, type TaskListItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const props = defineProps<{
    project: { id: number; name: string };
    tasks: Paginator<TaskListItem>;
    sprints: SelectOption[];
    filters: { search?: string; status?: string; sprint_id?: string };
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Tasks', href: '#' },
];

const filterSearch   = ref(props.filters.search ?? '');
const filterStatus   = ref(props.filters.status ?? '');
const filterSprintId = ref(props.filters.sprint_id ?? '');

function applyFilters() {
    router.get(
        route('projects.tasks.index', props.project.id),
        {
            search:    filterSearch.value || undefined,
            status:    filterStatus.value || undefined,
            sprint_id: filterSprintId.value || undefined,
        },
        { preserveScroll: true, replace: true },
    );
}

// Debounce search input, instant for selects
watch(filterSearch, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});
watch([filterStatus, filterSprintId], applyFilters);
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
                <div class="flex items-center gap-2">
                    <Link :href="route('projects.tasks.gantt', project.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Gantt
                    </Link>
                    <Link v-if="can.create" :href="route('projects.tasks.create', project.id)"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        New Task
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="filterSearch" type="text" placeholder="Search tasks…"
                        class="pl-8 pr-3 py-1.5 text-sm border border-slate-200 rounded-lg w-56 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                </div>
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
                <span class="text-xs text-slate-400 ml-auto">{{ tasks.total }} tasks</span>
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
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-24">Priority</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-28">Category</th>
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
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-red-100 text-red-700':    task.priority === 'critical',
                                        'bg-orange-100 text-orange-700': task.priority === 'high',
                                        'bg-amber-100 text-amber-700':   task.priority === 'medium',
                                        'bg-slate-100 text-slate-500':   task.priority === 'low',
                                    }">
                                    {{ task.priority_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <span v-if="task.category_label"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    {{ task.category_label }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
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
                <Pagination :paginator="tasks" class="px-5" />
            </div>
        </div>
    </AppLayout>
</template>
