<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import TaskFilters from '@/components/TaskFilters.vue';
import RequirementKanbanBoard from '@/components/RequirementKanbanBoard.vue';
import { type BreadcrumbItem, type Paginator, type TestCaseListItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import { ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    tcs: Paginator<TestCaseListItem> | null;
    boardTcs: TestCaseListItem[] | null;
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Test Cases', href: route('projects.test-cases.index', { project: props.project.id }) },
];

// ── View toggle ───────────────────────────────────────────────────────────────

const viewKey = `tc-view-${props.project.id}`;
const activeView = useLocalStorage<'list' | 'board'>(viewKey, 'list');

function setView(v: 'list' | 'board') {
    if (activeView.value === v) return;
    activeView.value = v;
    router.get(
        route('projects.test-cases.index', { project: props.project.id }),
        v === 'board' ? { view: 'board' } : {},
        { preserveScroll: true, replace: true },
    );
}

// ── Board filters (client-side) ───────────────────────────────────────────────

const filterPriorities = ref<string[]>([]);
const filterType       = ref('');

// ── Style maps ────────────────────────────────────────────────────────────────


const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

const runClass: Record<string, string> = {
    pass:    'bg-emerald-100 text-emerald-700',
    fail:    'bg-red-100 text-red-700',
    blocked: 'bg-amber-100 text-amber-700',
    skipped: 'bg-slate-100 text-slate-500',
};

const typeIcon: Record<string, string> = {
    manual:    '⊙',
    automated: '⚡',
};
</script>

<template>
    <Head title="Test Cases" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Test Cases</h1>
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
                        :href="route('projects.test-cases.import', { project: project.id })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import CSV
                    </Link>
                    <Link v-if="can.create"
                        :href="route('projects.test-cases.create', { project: project.id })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition">
                        + New TC
                    </Link>
                </div>
            </div>

            <!-- ── BOARD VIEW ──────────────────────────────────────────── -->
            <template v-if="activeView === 'board'">
                <div class="flex items-center gap-4 flex-wrap">
                    <TaskFilters
                        :priorities="filterPriorities"
                        category=""
                        :available-categories="[]"
                        @update:priorities="filterPriorities = $event"
                        @update:category="() => {}"
                    />
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-slate-500 font-medium">Type:</span>
                        <select v-model="filterType"
                            class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">All types</option>
                            <option value="manual">Manual</option>
                            <option value="automated">Automated</option>
                        </select>
                        <button v-if="filterType" type="button"
                            @click="filterType = ''"
                            class="text-xs text-slate-400 hover:text-slate-600 underline">
                            Clear
                        </button>
                    </div>
                </div>
                <RequirementKanbanBoard
                    :items="props.boardTcs ?? []"
                    :project-id="project.id"
                    show-route-name="projects.test-cases.show"
                    status-update-route-name="projects.test-cases.status.update"
                    :can-change-status="can.create"
                    :filter-priorities="filterPriorities"
                    :filter-type="filterType"
                    filter-category=""
                />
            </template>

            <!-- ── LIST VIEW ───────────────────────────────────────────── -->
            <template v-else>
                <div v-if="!tcs || tcs.data.length === 0" class="text-center py-16 text-slate-400">
                    <p class="text-lg font-medium">No test cases yet</p>
                    <p v-if="can.create" class="text-sm mt-1">Create the first one to get started.</p>
                </div>

                <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden animate-in">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Ref</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Priority</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Last Run</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500">Assignee</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tc in tcs.data" :key="tc.id"
                                class="hover:bg-slate-50 transition cursor-pointer"
                                @click="router.visit(route('projects.test-cases.show', { project: project.id, testCase: tc.id }))">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ tc.ref }}</td>
                                <td class="px-4 py-3 text-slate-800 font-medium">{{ tc.title }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">
                                    <span>{{ typeIcon[tc.type] }} {{ tc.type_label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <PriorityBadge :priority="tc.priority" :label="tc.priority_label" />
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusClass[tc.status]">
                                        {{ tc.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="tc.latest_run"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="runClass[tc.latest_run]">
                                        {{ tc.latest_run }}
                                    </span>
                                    <span v-else class="text-slate-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ tc.assignee?.name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :paginator="tcs" class="px-4" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
