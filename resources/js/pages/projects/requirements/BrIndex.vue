<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { type BreadcrumbItem, type BrListItem, type Paginator, type RequirementStatus } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Doughnut } from 'vue-chartjs';
import { doughnutOptions } from '@/composables/useChartSetup';
import { computed } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    brs: Paginator<BrListItem>;
    statusCounts: Partial<Record<RequirementStatus, number>>;
    can: { create: boolean };
}>();

const statusChartData = computed(() => ({
    labels: ['Draft', 'In Review', 'Approved', 'Implemented', 'Deprecated'],
    datasets: [{
        data: [
            props.statusCounts.draft ?? 0,
            props.statusCounts.review ?? 0,
            props.statusCounts.approved ?? 0,
            props.statusCounts.implemented ?? 0,
            props.statusCounts.deprecated ?? 0,
        ],
        backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'],
        borderWidth: 0,
    }],
}));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
];

const priorityClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-slate-100 text-slate-500',
};

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};
</script>

<template>
    <Head title="Business Requirements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Business Requirements</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('projects.requirements.business.graph', project.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Dependency Graph
                    </Link>
                    <Link v-if="can.create"
                        :href="route('projects.requirements.business.import', project.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import CSV
                    </Link>
                    <Link v-if="can.create"
                        :href="route('projects.requirements.business.create', project.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition">
                        + New BR
                    </Link>
                </div>
            </div>

            <!-- Status distribution chart -->
            <div v-if="brs.total > 0" class="bg-white rounded-xl border border-slate-200 p-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Status Distribution</h2>
                <div style="height: 180px; max-width: 280px;">
                    <Doughnut :data="statusChartData" :options="doughnutOptions" />
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="brs.data.length === 0" class="text-center py-16 text-slate-400">
                <p class="text-lg font-medium">No business requirements yet</p>
                <p v-if="can.create" class="text-sm mt-1">Create the first one to get started.</p>
            </div>

            <!-- Table -->
            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden animate-in">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Ref</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Title</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Priority</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Category</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">TRs</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Blockers</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Created by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="br in brs.data" :key="br.id"
                            class="hover:bg-slate-50 transition cursor-pointer"
                            @click="$inertia.visit(route('projects.requirements.business.show', [project.id, br.id]))">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ br.ref }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">
                                {{ br.title }}
                                <span v-if="br.is_blocked"
                                    class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">
                                    Blocked
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="priorityClass[br.priority]">
                                    {{ br.priority_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass[br.status]">
                                    {{ br.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ br.category ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ br.tr_count }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                <span v-if="br.blocking_count > 0" class="text-amber-600 font-medium">{{ br.blocking_count }}</span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ br.creator?.name }}</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :paginator="brs" class="px-4" />
            </div>
        </div>
    </AppLayout>
</template>
