<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import RequirementKanbanBoard from '@/components/RequirementKanbanBoard.vue';
import { type BreadcrumbItem, type Paginator, type TrListItem, type RequirementStatus } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import { Doughnut } from 'vue-chartjs';
import { doughnutOptions } from '@/composables/useChartSetup';
import { computed, ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    trs: Paginator<TrListItem> | null;
    boardTrs: TrListItem[] | null;
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
    { title: 'Technical Requirements', href: route('projects.requirements.technical.index', { project: props.project.id }) },
];

// ── View toggle ───────────────────────────────────────────────────────────────

const viewKey = `tr-view-${props.project.id}`;
const activeView = useLocalStorage<'list' | 'board'>(viewKey, 'list');

function setView(v: 'list' | 'board') {
    if (activeView.value === v) return;
    activeView.value = v;
    router.get(
        route('projects.requirements.technical.index', { project: props.project.id }),
        v === 'board' ? { view: 'board' } : {},
        { preserveScroll: true, replace: true },
    );
}

// ── Board filter: type (client-side) ──────────────────────────────────────────

const filterType = ref('');

// ── Style maps ────────────────────────────────────────────────────────────────

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

const typeClass: Record<string, string> = {
    functional:     'bg-sky-100 text-sky-700',
    non_functional: 'bg-purple-100 text-purple-700',
    constraint:     'bg-rose-100 text-rose-700',
};
</script>

<template>
    <Head title="Technical Requirements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Technical Requirements</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- List / Board toggle -->
                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
                        <button @click="setView('list')"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="activeView === 'list' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'">
                            List
                        </button>
                        <button @click="setView('board')"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="activeView === 'board' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'">
                            Board
                        </button>
                    </div>
                    <Link v-if="can.create"
                        :href="route('projects.requirements.technical.import', { project: project.id })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import CSV
                    </Link>
                    <Link v-if="can.create"
                        :href="route('projects.requirements.technical.create', { project: project.id })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition">
                        + New TR
                    </Link>
                </div>
            </div>

            <!-- ── BOARD VIEW ──────────────────────────────────────────── -->
            <template v-if="activeView === 'board'">
                <!-- Type filter -->
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500 font-medium">Type:</span>
                    <select v-model="filterType"
                        class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">All types</option>
                        <option value="functional">Functional</option>
                        <option value="non_functional">Non-Functional</option>
                        <option value="constraint">Constraint</option>
                    </select>
                    <button v-if="filterType" type="button"
                        @click="filterType = ''"
                        class="text-xs text-slate-400 hover:text-slate-600 underline">
                        Clear
                    </button>
                </div>
                <RequirementKanbanBoard
                    :items="props.boardTrs ?? []"
                    :project-id="project.id"
                    show-route-name="projects.requirements.technical.show"
                    status-update-route-name="projects.requirements.technical.status.update"
                    :can-change-status="can.create"
                    :filter-priorities="[]"
                    :filter-type="filterType"
                    filter-category=""
                />
            </template>

            <!-- ── LIST VIEW ───────────────────────────────────────────── -->
            <template v-else>
                <!-- Status distribution chart -->
                <div v-if="trs && trs.total > 0" class="bg-white rounded-xl border border-slate-200 p-4">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">Status Distribution</h2>
                    <div style="height: 180px; max-width: 280px;">
                        <Doughnut :data="statusChartData" :options="doughnutOptions" />
                    </div>
                </div>

                <div v-if="!trs || trs.data.length === 0" class="text-center py-16 text-slate-400">
                    <p class="text-lg font-medium">No technical requirements yet</p>
                    <p v-if="can.create" class="text-sm mt-1">Create the first one to get started.</p>
                </div>

                <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden animate-in">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Ref</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">BRs</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Created by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tr in trs.data" :key="tr.id"
                                class="hover:bg-slate-50 transition cursor-pointer"
                                @click="router.visit(route('projects.requirements.technical.show', { project: project.id, technicalRequirement: tr.id }))">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ tr.ref }}</td>
                                <td class="px-4 py-3 text-slate-800 font-medium">{{ tr.title }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="typeClass[tr.type]">
                                        {{ tr.type_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusClass[tr.status]">
                                        {{ tr.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ tr.br_count }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ tr.creator?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :paginator="trs" class="px-4" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
