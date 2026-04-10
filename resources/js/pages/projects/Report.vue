<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type RequirementStatus, type TaskStatus, type TestRunStatus } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    project: { id: number; name: string };
    br: {
        total: number;
        by_status: Partial<Record<RequirementStatus, number>>;
        coverage: number;
        covered: number;
    };
    tr: {
        total: number;
        by_status: Partial<Record<RequirementStatus, number>>;
        coverage: number;
        covered: number;
    };
    tc: {
        total: number;
        by_status: Partial<Record<RequirementStatus, number>>;
        latest_runs: Partial<Record<TestRunStatus | 'not_run', number>>;
    };
    tasks: {
        total: number;
        by_status: Partial<Record<TaskStatus, number>>;
        overdue: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Reports', href: '#' },
];

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const },
    },
    cutout: '65%',
};

const brChartData = computed(() => ({
    labels: ['Draft', 'In Review', 'Approved', 'Implemented', 'Deprecated'],
    datasets: [{
        data: [
            props.br.by_status.draft ?? 0,
            props.br.by_status.review ?? 0,
            props.br.by_status.approved ?? 0,
            props.br.by_status.implemented ?? 0,
            props.br.by_status.deprecated ?? 0,
        ],
        backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'],
        borderWidth: 0,
    }],
}));

const trChartData = computed(() => ({
    labels: ['Draft', 'In Review', 'Approved', 'Implemented', 'Deprecated'],
    datasets: [{
        data: [
            props.tr.by_status.draft ?? 0,
            props.tr.by_status.review ?? 0,
            props.tr.by_status.approved ?? 0,
            props.tr.by_status.implemented ?? 0,
            props.tr.by_status.deprecated ?? 0,
        ],
        backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'],
        borderWidth: 0,
    }],
}));

const tcRunChartData = computed(() => ({
    labels: ['Not Run', 'Pass', 'Fail', 'Blocked', 'Skipped'],
    datasets: [{
        data: [
            props.tc.latest_runs.not_run ?? 0,
            props.tc.latest_runs.pass ?? 0,
            props.tc.latest_runs.fail ?? 0,
            props.tc.latest_runs.blocked ?? 0,
            props.tc.latest_runs.skipped ?? 0,
        ],
        backgroundColor: ['#94a3b8', '#10b981', '#ef4444', '#f59e0b', '#64748b'],
        borderWidth: 0,
    }],
}));

const taskChartData = computed(() => ({
    labels: ['To Do', 'In Progress', 'Done', 'Cancelled'],
    datasets: [{
        data: [
            props.tasks.by_status.todo ?? 0,
            props.tasks.by_status.in_progress ?? 0,
            props.tasks.by_status.done ?? 0,
            props.tasks.by_status.cancelled ?? 0,
        ],
        backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#ef4444'],
        borderWidth: 0,
    }],
}));

function coverageColor(pct: number) {
    if (pct >= 80) return 'text-emerald-600';
    if (pct >= 50) return 'text-amber-500';
    return 'text-red-500';
}
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Project Health Report</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
            </div>

            <!-- Summary cards grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                <!-- BR card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Business Requirements</h2>
                        <span class="text-2xl font-bold text-slate-900">{{ br.total }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Coverage</span>
                        <span :class="coverageColor(br.coverage)" class="font-semibold text-sm">{{ br.coverage }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div
                            class="h-1.5 rounded-full transition-all"
                            :class="br.coverage >= 80 ? 'bg-emerald-500' : br.coverage >= 50 ? 'bg-amber-400' : 'bg-red-400'"
                            :style="{ width: br.coverage + '%' }"
                        />
                    </div>
                    <p class="text-xs text-slate-400">{{ br.covered }} of {{ br.total }} covered</p>
                    <div v-if="br.total > 0" style="height: 160px;">
                        <Doughnut :data="brChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- TR card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Technical Requirements</h2>
                        <span class="text-2xl font-bold text-slate-900">{{ tr.total }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Coverage</span>
                        <span :class="coverageColor(tr.coverage)" class="font-semibold text-sm">{{ tr.coverage }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div
                            class="h-1.5 rounded-full transition-all"
                            :class="tr.coverage >= 80 ? 'bg-emerald-500' : tr.coverage >= 50 ? 'bg-amber-400' : 'bg-red-400'"
                            :style="{ width: tr.coverage + '%' }"
                        />
                    </div>
                    <p class="text-xs text-slate-400">{{ tr.covered }} of {{ tr.total }} covered</p>
                    <div v-if="tr.total > 0" style="height: 160px;">
                        <Doughnut :data="trChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- TC card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Test Cases</h2>
                        <span class="text-2xl font-bold text-slate-900">{{ tc.total }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Latest run results</span>
                    </div>
                    <div class="space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"/>Pass</span>
                            <span class="font-medium">{{ tc.latest_runs.pass ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"/>Fail</span>
                            <span class="font-medium">{{ tc.latest_runs.fail ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"/>Blocked</span>
                            <span class="font-medium">{{ tc.latest_runs.blocked ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400 inline-block"/>Not Run</span>
                            <span class="font-medium">{{ tc.latest_runs.not_run ?? 0 }}</span>
                        </div>
                    </div>
                    <div v-if="tc.total > 0" style="height: 160px;">
                        <Doughnut :data="tcRunChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Tasks card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Tasks</h2>
                        <span class="text-2xl font-bold text-slate-900">{{ tasks.total }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Overdue</span>
                        <span :class="tasks.overdue > 0 ? 'text-red-600 font-semibold' : 'text-slate-400'">{{ tasks.overdue }}</span>
                    </div>
                    <div class="space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400 inline-block"/>To Do</span>
                            <span class="font-medium">{{ tasks.by_status.todo ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"/>In Progress</span>
                            <span class="font-medium">{{ tasks.by_status.in_progress ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"/>Done</span>
                            <span class="font-medium">{{ tasks.by_status.done ?? 0 }}</span>
                        </div>
                    </div>
                    <div v-if="tasks.total > 0" style="height: 160px;">
                        <Doughnut :data="taskChartData" :options="chartOptions" />
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
