<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import TaskStatusSelect from '@/components/TaskStatusSelect.vue';
import TaskKanbanBoard from '@/components/TaskKanbanBoard.vue';
import TaskFilters from '@/components/TaskFilters.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import { type BreadcrumbItem, type Paginator, type SelectOption, type TaskListItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

let searchTimer: ReturnType<typeof setTimeout> | null = null;

type MemberOption = { value: number; label: string };

const props = defineProps<{
    project: { id: number; name: string };
    tasks: Paginator<TaskListItem> | null;
    boardTasks: TaskListItem[];
    members: MemberOption[];
    sprints: SelectOption[];
    filters: { search?: string; status?: string; sprint_id?: string };
    can: { create: boolean; change_status: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Tasks', href: '#' },
];

// ── View toggle ───────────────────────────────────────────────────────────────

const viewKey = `tasks-view-${props.project.id}`;
const activeView = useLocalStorage<'list' | 'board'>(viewKey, 'list');

function setView(v: 'list' | 'board') {
    if (activeView.value === v) return;
    activeView.value = v;
    router.get(
        route('projects.tasks.index', { project: props.project.id }),
        { ...currentListFilters(), view: v === 'board' ? 'board' : undefined },
        { preserveScroll: true, replace: true },
    );
}

function currentListFilters() {
    return {
        sprint_id: filterSprintId.value || undefined,
        search:    filterSearch.value   || undefined,
        status:    filterStatus.value   || undefined,
    };
}

// ── List-mode filters (server-side) ───────────────────────────────────────────

const filterSearch   = ref(props.filters.search   ?? '');
const filterStatus   = ref(props.filters.status   ?? '');
const filterSprintId = ref(props.filters.sprint_id ?? '');

function applyListFilters() {
    router.get(
        route('projects.tasks.index', { project: props.project.id }),
        { ...currentListFilters(), view: undefined },
        { preserveScroll: true, replace: true },
    );
}

watch(filterSearch, () => {
    if (activeView.value !== 'list') return;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyListFilters, 350);
});
watch([filterStatus, filterSprintId], () => {
    if (activeView.value === 'list') applyListFilters();
});

// ── Board-mode filters (client-side) ──────────────────────────────────────────

const filterPriorities = ref<string[]>([]);
const filterCategory   = ref('');

// Available categories derived from the current board task set
const availableCategories = computed(() => {
    const seen = new Map<string, string>();
    for (const t of props.boardTasks) {
        if (t.category && t.category_label && !seen.has(t.category)) {
            seen.set(t.category, t.category_label);
        }
    }
    return [...seen.entries()].map(([value, label]) => ({ value, label }));
});
</script>

<template>
    <Head title="Tasks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5 animate-in">

            <!-- Page header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Tasks</h1>
                    <p class="text-sm text-slate-500 mt-0.5">All tasks for {{ project.name }}.</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- List / Board toggle -->
                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
                        <button @click="setView('list')"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="activeView === 'list'
                                ? 'bg-white shadow text-slate-800'
                                : 'text-slate-500 hover:text-slate-700'">
                            List
                        </button>
                        <button @click="setView('board')"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="activeView === 'board'
                                ? 'bg-white shadow text-slate-800'
                                : 'text-slate-500 hover:text-slate-700'">
                            Board
                        </button>
                    </div>

                    <Link :href="route('projects.tasks.calendar', { project: project.id })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Calendar
                    </Link>
                    <Link :href="route('projects.tasks.gantt', { project: project.id })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Gantt
                    </Link>
                    <Link v-if="can.create" :href="route('projects.tasks.create', { project: project.id })"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        New Task
                    </Link>
                </div>
            </div>

            <!-- ── BOARD VIEW ─────────────────────────────────────────────── -->
            <template v-if="activeView === 'board'">
                <!-- Board filters (client-side) -->
                <div class="flex items-center gap-3 flex-wrap">
                    <select v-model="filterSprintId"
                        @change="router.get(route('projects.tasks.index', { project: project.id }), { view: 'board', sprint_id: filterSprintId || undefined }, { preserveScroll: true, replace: true })"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">All sprints</option>
                        <option value="none">No sprint</option>
                        <option v-for="s in sprints" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <div class="w-px h-5 bg-slate-200" />
                    <TaskFilters
                        :priorities="filterPriorities"
                        :category="filterCategory"
                        :available-categories="availableCategories"
                        @update:priorities="filterPriorities = $event"
                        @update:category="filterCategory = $event"
                    />
                </div>

                <TaskKanbanBoard
                    :tasks="boardTasks"
                    :project-id="project.id"
                    :members="members"
                    :can-create="can.create"
                    :can-change-status="can.change_status"
                    :filter-priorities="filterPriorities"
                    :filter-category="filterCategory"
                />
            </template>

            <!-- ── LIST VIEW ──────────────────────────────────────────────── -->
            <template v-else>
                <!-- List filters (server-side) -->
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
                    <span v-if="tasks" class="text-xs text-slate-400 ml-auto">{{ tasks.total }} tasks</span>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div v-if="!tasks || tasks.data.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
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
                                    <PriorityBadge :priority="task.priority" :label="task.priority_label" />
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
                    <Pagination v-if="tasks" :paginator="tasks" class="px-5" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
