<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TaskStatusSelect from '@/components/TaskStatusSelect.vue';
import TaskKanbanBoard from '@/components/TaskKanbanBoard.vue';
import TaskFilters from '@/components/TaskFilters.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import { type BreadcrumbItem, type BurndownData, type Sprint, type TaskListItem, type WorkloadEntry } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

type MemberOption = { value: number; label: string };

interface AvailableBr {
    id: number;
    ref: string;
    title: string;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    is_blocked: boolean;
    has_pert: boolean;
    pert_expected: number | null;
    committed: boolean;
}

interface CapacityRecord {
    user_id: number;
    user_name: string;
    year: number;
    month: number;
    available_hours: number;
    focus_factor: number;
    effective_hours: number;
    notes: string | null;
}

interface PlanningData {
    available_brs: AvailableBr[];
    pert_expected: number;
    pert_std_dev: number;
    available_hours: number;
    buffer: number;
    confidence: {
        '68': { low: number; high: number };
        '95': { low: number; high: number };
        '99': { low: number; high: number };
    };
    unestimated_count: number;
}

const props = defineProps<{
    project: { id: number; name: string };
    sprint: Sprint & { is_active: boolean; is_closed: boolean; tasks: TaskListItem[] };
    members: MemberOption[];
    workload: WorkloadEntry[];
    burndown: BurndownData;
    planning: PlanningData;
    capacity_records: CapacityRecord[];
    can: {
        edit: boolean;
        delete: boolean;
        create_task: boolean;
        change_status: boolean;
        manage_capacity: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Sprints', href: route('projects.sprints.index', props.project.id) },
    { title: props.sprint.name, href: '#' },
];

// ── Tabs ──────────────────────────────────────────────────────────────────────
const activeTab = useLocalStorage<'tasks' | 'planning'>(`sprint-tab-${props.sprint.id}`, 'tasks');

// ── Task view toggle ──────────────────────────────────────────────────────────
const tasksViewKey = computed(() => `sprint-tasks-view-${props.sprint.id}`);
const tasksView = useLocalStorage<'list' | 'board'>(tasksViewKey.value, 'list');

const filterPriorities = ref<string[]>([]);
const filterCategory   = ref('');

const availableCategories = computed(() => {
    const seen = new Map<string, string>();
    for (const t of props.sprint.tasks) {
        if (t.category && t.category_label && !seen.has(t.category)) {
            seen.set(t.category, t.category_label);
        }
    }
    return [...seen.entries()].map(([value, label]) => ({ value, label }));
});

// ── Progress stats ────────────────────────────────────────────────────────────
const tasksByStatus = (status: string) => props.sprint.tasks.filter(t => t.status === status);
const doneTasks = () => tasksByStatus('done').length;
const totalTasks = () => props.sprint.tasks.length;
const progress = () => totalTasks() ? Math.round((doneTasks() / totalTasks()) * 100) : 0;

// ── Burndown chart ────────────────────────────────────────────────────────────
const chartData = {
    labels: props.burndown.labels,
    datasets: [
        {
            label: 'Ideal',
            data: props.burndown.ideal,
            borderColor: '#94a3b8',
            borderDash: [5, 5],
            pointRadius: 0,
            tension: 0,
            fill: false,
        },
        {
            label: 'Actual Remaining',
            data: props.burndown.actual,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            pointRadius: 3,
            tension: 0.3,
            fill: true,
        },
    ],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' as const, labels: { font: { size: 11 } } },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        y: {
            beginAtZero: true,
            title: { display: true, text: 'Hours remaining', font: { size: 11 } },
        },
        x: { grid: { display: false } },
    },
};

// ── Planning ──────────────────────────────────────────────────────────────────
const brSearch = ref('');

const filteredAvailableBrs = computed(() => {
    const q = brSearch.value.toLowerCase();
    return props.planning.available_brs.filter(br =>
        !q || br.title.toLowerCase().includes(q) || br.ref.toLowerCase().includes(q)
    );
});

const committedBrs = computed(() => props.planning.available_brs.filter(b => b.committed));

function toggleBr(br: AvailableBr) {
    if (br.committed) {
        router.delete(
            route('projects.sprints.brs.destroy', { project: props.project.id, sprint: props.sprint.id, businessRequirement: br.id }),
            { preserveScroll: true }
        );
    } else {
        router.post(
            route('projects.sprints.brs.store', { project: props.project.id, sprint: props.sprint.id }),
            { business_requirement_id: br.id },
            { preserveScroll: true }
        );
    }
}

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

function closeSprint() {
    if (confirm('Close this sprint and record velocity? This cannot be undone.')) {
        router.post(route('projects.sprints.close', [props.project.id, props.sprint.id]));
    }
}

function confirmDelete() {
    if (confirm(`Delete "${props.sprint.name}"? Tasks will be unassigned from this sprint.`)) {
        router.delete(route('projects.sprints.destroy', [props.project.id, props.sprint.id]));
    }
}
</script>

<template>
    <Head :title="sprint.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h1 class="text-xl font-semibold text-slate-900">{{ sprint.name }}</h1>
                        <span v-if="sprint.is_active" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Active</span>
                        <span v-if="sprint.is_closed" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Closed</span>
                    </div>
                    <p class="text-sm text-slate-500">{{ sprint.start_date }} → {{ sprint.end_date }}
                        <span v-if="sprint.capacity" class="ml-2">· {{ sprint.capacity }} hrs capacity</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="can.edit && !sprint.is_closed" @click="closeSprint"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        Close Sprint
                    </button>
                    <Link v-if="can.edit" :href="route('projects.sprints.edit', [project.id, sprint.id])"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        Edit
                    </Link>
                    <button v-if="can.delete" @click="confirmDelete"
                        class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700">Sprint Progress</span>
                    <span class="text-sm font-bold text-slate-900">{{ doneTasks() }} / {{ totalTasks() }} tasks done</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3">
                    <div class="h-3 rounded-full bg-emerald-500 transition-all" :style="{ width: progress() + '%' }" />
                </div>
                <p class="text-xs text-slate-400 mt-1.5">{{ progress() }}% complete</p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-slate-200">
                <nav class="flex gap-6 text-sm font-medium">
                    <button @click="activeTab = 'tasks'"
                        class="pb-2 border-b-2 transition-colors"
                        :class="activeTab === 'tasks' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        Tasks
                    </button>
                    <button @click="activeTab = 'planning'"
                        class="pb-2 border-b-2 transition-colors flex items-center gap-1.5"
                        :class="activeTab === 'planning' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        Planning
                        <span v-if="planning.unestimated_count > 0"
                            class="text-[10px] bg-amber-100 text-amber-700 px-1.5 rounded-full">
                            {{ planning.unestimated_count }} unestimated
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ── TASKS TAB ──────────────────────────────────────────────── -->
            <template v-if="activeTab === 'tasks'">
                <!-- Burndown + Workload -->
                <div class="grid grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white border border-slate-200 rounded-xl p-5">
                        <h2 class="text-sm font-semibold text-slate-700 mb-4">Burndown Chart</h2>
                        <div v-if="burndown.total_planned === 0" class="flex items-center justify-center h-48 text-slate-400 text-sm">
                            No hour-estimated tasks in this sprint.
                        </div>
                        <div v-else style="height: 220px;">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5">
                        <h2 class="text-sm font-semibold text-slate-700 mb-4">Workload</h2>
                        <div v-if="workload.length === 0" class="text-sm text-slate-400 italic">No assigned tasks.</div>
                        <div v-for="entry in workload" :key="entry.assignee_id" class="mb-4 last:mb-0">
                            <p class="text-xs font-semibold text-slate-700 mb-1">{{ entry.assignee_name }}</p>
                            <div v-if="entry.hours.planned > 0" class="mb-1">
                                <div class="flex items-center justify-between text-[10px] text-slate-500 mb-0.5">
                                    <span>Hours</span>
                                    <span>{{ entry.hours.actual }}h actual / {{ entry.hours.planned }}h planned</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all"
                                        :style="{ width: Math.min(100, (entry.hours.actual / entry.hours.planned) * 100) + '%' }"
                                        :class="entry.hours.actual > entry.hours.planned ? 'bg-red-500' : 'bg-primary'" />
                                </div>
                                <p v-if="entry.hours.actual > entry.hours.planned" class="text-[10px] text-red-500 mt-0.5">Overallocated</p>
                            </div>
                            <div v-if="entry.points.planned > 0" class="text-[10px] text-slate-500">
                                Points: {{ entry.points.done }} / {{ entry.points.planned }} pts done
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Tasks</h2>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
                                <button @click="tasksView = 'list'"
                                    class="px-3 py-1.5 rounded-md transition-colors"
                                    :class="tasksView === 'list' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'">
                                    List
                                </button>
                                <button @click="tasksView = 'board'"
                                    class="px-3 py-1.5 rounded-md transition-colors"
                                    :class="tasksView === 'board' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'">
                                    Board
                                </button>
                            </div>
                            <Link v-if="can.create_task" :href="route('projects.tasks.create', { project: project.id })"
                                class="text-xs text-primary font-medium hover:underline">
                                + Add Task
                            </Link>
                        </div>
                    </div>

                    <template v-if="tasksView === 'board'">
                        <TaskFilters
                            :priorities="filterPriorities"
                            :category="filterCategory"
                            :available-categories="availableCategories"
                            @update:priorities="filterPriorities = $event"
                            @update:category="filterCategory = $event"
                        />
                        <TaskKanbanBoard
                            :tasks="sprint.tasks"
                            :project-id="project.id"
                            :sprint-id="sprint.id"
                            :members="members"
                            :can-create="can.create_task"
                            :can-change-status="can.change_status"
                            :filter-priorities="filterPriorities"
                            :filter-category="filterCategory"
                        />
                    </template>

                    <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <div v-if="sprint.tasks.length === 0" class="px-5 py-8 text-center text-sm text-slate-400">
                            No tasks in this sprint. Assign tasks from the Tasks page.
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Task</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-28">Status</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-28">Effort</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-32">Assignee</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-24">Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="task in sprint.tasks" :key="task.id" class="hover:bg-slate-50">
                                    <td class="px-5 py-3">
                                        <Link :href="route('projects.tasks.show', { project: project.id, task: task.id })"
                                            class="text-slate-800 hover:text-primary font-medium">
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
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ task.assignee?.name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-xs text-slate-400">{{ task.due_date ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- ── PLANNING TAB ───────────────────────────────────────────── -->
            <template v-else>
                <div class="grid grid-cols-3 gap-6">
                    <!-- BR Selection -->
                    <div class="col-span-2 space-y-4">
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-slate-700">Business Requirements</h2>
                                <span class="text-xs text-slate-400">{{ committedBrs.length }} committed</span>
                            </div>

                            <!-- Search -->
                            <div class="px-4 py-3 border-b border-slate-100">
                                <input v-model="brSearch" type="text" placeholder="Search BRs…"
                                    class="w-full text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>

                            <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
                                <div v-if="filteredAvailableBrs.length === 0" class="px-5 py-6 text-center text-sm text-slate-400">
                                    No BRs available.
                                </div>
                                <div v-for="br in filteredAvailableBrs" :key="br.id"
                                    class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 transition">
                                    <input type="checkbox" :checked="br.committed"
                                        :disabled="!can.manage_capacity"
                                        @change="toggleBr(br)"
                                        class="rounded border-slate-300 text-primary focus:ring-primary/40 cursor-pointer" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono text-xs text-slate-400">{{ br.ref }}</span>
                                            <PriorityBadge :priority="br.priority" :label="br.priority_label" size="xs" />
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                :class="statusClass[br.status]">
                                                {{ br.status_label }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-700 truncate mt-0.5">{{ br.title }}</p>
                                    </div>
                                    <div class="flex-shrink-0 text-right">
                                        <span v-if="br.has_pert" class="text-sm font-medium text-slate-700">
                                            {{ Number(br.pert_expected).toFixed(1) }} h
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-xs text-amber-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            No estimate
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PERT Summary -->
                    <div class="space-y-4">
                        <!-- Capacity summary -->
                        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
                            <h2 class="text-sm font-semibold text-slate-700">Capacity Summary</h2>

                            <div v-if="planning.unestimated_count > 0"
                                class="flex items-start gap-2 text-xs text-amber-600 bg-amber-50 rounded-lg p-2.5">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ planning.unestimated_count }} committed BR{{ planning.unestimated_count > 1 ? 's are' : ' is' }} missing PERT estimates.
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Expected effort</span>
                                    <span class="font-semibold text-slate-800">{{ planning.pert_expected.toFixed(1) }} h</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Std deviation</span>
                                    <span class="text-slate-600">±{{ planning.pert_std_dev.toFixed(1) }} h</span>
                                </div>
                                <div class="border-t border-slate-100 pt-3 flex justify-between">
                                    <span class="text-slate-500">Team available</span>
                                    <span class="font-semibold text-slate-800">{{ planning.available_hours.toFixed(1) }} h</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Buffer</span>
                                    <span class="font-semibold" :class="planning.buffer >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ planning.buffer >= 0 ? '+' : '' }}{{ planning.buffer.toFixed(1) }} h
                                    </span>
                                </div>
                            </div>

                            <!-- Confidence ranges -->
                            <div class="border-t border-slate-100 pt-3 space-y-2">
                                <p class="text-xs font-medium text-slate-500 mb-2">Confidence Ranges</p>
                                <div v-for="(conf, pct) in planning.confidence" :key="pct"
                                    class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">{{ pct }}%</span>
                                    <span class="text-slate-700">
                                        {{ conf.low.toFixed(1) }} – {{ conf.high.toFixed(1) }} h
                                        <span v-if="planning.available_hours >= conf.low && planning.available_hours <= conf.high"
                                            class="ml-1 text-emerald-500">✓</span>
                                        <span v-else-if="planning.available_hours < conf.low"
                                            class="ml-1 text-red-500">✗</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Team capacity records -->
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <h2 class="text-sm font-semibold text-slate-700">Team Capacity</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Prorated across sprint window</p>
                            </div>
                            <div v-if="capacity_records.length === 0" class="px-4 py-4 text-xs text-slate-400 italic">
                                No capacity records. Add them on the Members page.
                            </div>
                            <div v-else class="divide-y divide-slate-100">
                                <div v-for="r in capacity_records" :key="`${r.user_id}-${r.year}-${r.month}`"
                                    class="px-4 py-2.5 text-xs">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-slate-700">{{ r.user_name }}</span>
                                        <span class="text-slate-500">{{ r.effective_hours.toFixed(1) }} h eff.</span>
                                    </div>
                                    <div class="text-slate-400 mt-0.5">
                                        {{ r.available_hours }}h × {{ (r.focus_factor * 100).toFixed(0) }}% focus
                                        <span v-if="r.notes" class="ml-1 italic">· {{ r.notes }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
