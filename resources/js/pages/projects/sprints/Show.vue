<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TaskStatusSelect from '@/components/TaskStatusSelect.vue';
import { type BreadcrumbItem, type BurndownData, type Sprint, type TaskListItem, type WorkloadEntry } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
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

const props = defineProps<{
    project: { id: number; name: string };
    sprint: Sprint & { is_active: boolean; tasks: TaskListItem[] };
    workload: WorkloadEntry[];
    burndown: BurndownData;
    can: { edit: boolean; delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Sprints', href: route('projects.sprints.index', props.project.id) },
    { title: props.sprint.name, href: '#' },
];


const tasksByStatus = (status: string) => props.sprint.tasks.filter(t => t.status === status);
const doneTasks = () => tasksByStatus('done').length;
const totalTasks = () => props.sprint.tasks.length;
const progress = () => totalTasks() ? Math.round((doneTasks() / totalTasks()) * 100) : 0;

// Burndown chart config
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
                    </div>
                    <p class="text-sm text-slate-500">{{ sprint.start_date }} → {{ sprint.end_date }}
                        <span v-if="sprint.capacity" class="ml-2">· {{ sprint.capacity }} hrs capacity</span>
                    </p>
                </div>
                <div v-if="can.edit || can.delete" class="flex items-center gap-2">
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

            <!-- Burndown + Workload -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Burndown chart -->
                <div class="col-span-2 bg-white border border-slate-200 rounded-xl p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Burndown Chart</h2>
                    <div v-if="burndown.total_planned === 0" class="flex items-center justify-center h-48 text-slate-400 text-sm">
                        No hour-estimated tasks in this sprint.
                    </div>
                    <div v-else style="height: 220px;">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Workload -->
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
                                <div class="h-1.5 rounded-full bg-primary transition-all"
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

            <!-- Tasks table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Tasks</h2>
                    <Link :href="route('projects.tasks.create', project.id)"
                        class="text-xs text-primary font-medium hover:underline">
                        + Add Task
                    </Link>
                </div>
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
                                <Link :href="route('projects.tasks.show', [project.id, task.id])"
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
    </AppLayout>
</template>
