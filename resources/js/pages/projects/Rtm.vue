<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type RtmBr, type RtmTr, type RtmTestCase } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RtmGraph from '@/components/RtmGraph.vue';

const props = defineProps<{
    project: { id: number; name: string };
    matrix: RtmBr[];
    can: { export: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'RTM', href: '#' },
];

const activeTab = ref<'matrix' | 'grid' | 'graph' | 'gaps'>('matrix');

// --- Filters ---
const filterStatus = ref('');
const filterCoverage = ref('');

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

const priorityClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-slate-100 text-slate-500',
};

const runClass: Record<string, string> = {
    pass:    'bg-emerald-100 text-emerald-700',
    fail:    'bg-red-100 text-red-700',
    blocked: 'bg-amber-100 text-amber-700',
    skipped: 'bg-slate-100 text-slate-500',
};

const typeClass: Record<string, string> = {
    functional:     'bg-sky-100 text-sky-700',
    non_functional: 'bg-purple-100 text-purple-700',
    constraint:     'bg-rose-100 text-rose-700',
};

// --- Waffle grid data ---
// --- Waffle grid mode ---
const gridMode = ref<'br-tr' | 'tr-tc'>('br-tr');

// ── Mode: BR rows × TR columns ──────────────────────────────────────────────
const allTrs = computed(() => {
    const seen = new Map<number, RtmTr>();
    for (const br of props.matrix) {
        for (const tr of br.trs) {
            if (!seen.has(tr.id)) seen.set(tr.id, tr);
        }
    }
    return [...seen.values()].sort((a, b) => a.ref.localeCompare(b.ref));
});

const brTrMap = computed(() => {
    const map = new Map<number, Map<number, { is_covered: boolean; tc_count: number; passing_count: number }>>();
    for (const br of props.matrix) {
        const trMap = new Map<number, { is_covered: boolean; tc_count: number; passing_count: number }>();
        for (const tr of br.trs) {
            trMap.set(tr.id, {
                is_covered:    tr.is_covered,
                tc_count:      tr.test_cases.length,
                passing_count: tr.test_cases.filter(tc => tc.is_passing).length,
            });
        }
        map.set(br.id, trMap);
    }
    return map;
});

function brTrCell(brId: number, trId: number) {
    return brTrMap.value.get(brId)?.get(trId) ?? null;
}
function brTrCellClass(brId: number, trId: number): string {
    const c = brTrCell(brId, trId);
    if (!c) return 'bg-slate-100';
    if (c.is_covered)   return 'bg-emerald-500';
    if (c.tc_count > 0) return 'bg-amber-400';
    return 'bg-red-400';
}
function brTrCellTitle(brId: number, trId: number): string {
    const c = brTrCell(brId, trId);
    if (!c) return 'Not linked';
    if (c.is_covered)   return `Covered — ${c.passing_count}/${c.tc_count} TCs passing`;
    if (c.tc_count > 0) return `${c.tc_count} TC(s) linked, none passing`;
    return 'Linked — no test cases';
}

// ── Mode: TR rows × TC columns ──────────────────────────────────────────────
const allTcs = computed(() => {
    const seen = new Map<number, RtmTestCase>();
    for (const br of props.matrix) {
        for (const tr of br.trs) {
            for (const tc of tr.test_cases) {
                if (!seen.has(tc.id)) seen.set(tc.id, tc);
            }
        }
    }
    return [...seen.values()].sort((a, b) => a.ref.localeCompare(b.ref));
});

const trTcMap = computed(() => {
    const map = new Map<number, Map<number, { latest_run: string | null; is_passing: boolean }>>();
    for (const br of props.matrix) {
        for (const tr of br.trs) {
            if (!map.has(tr.id)) map.set(tr.id, new Map());
            for (const tc of tr.test_cases) {
                map.get(tr.id)!.set(tc.id, {
                    latest_run: tc.latest_run,
                    is_passing: tc.is_passing,
                });
            }
        }
    }
    return map;
});

function trTcCell(trId: number, tcId: number) {
    return trTcMap.value.get(trId)?.get(tcId) ?? null;
}
function trTcCellClass(trId: number, tcId: number): string {
    const c = trTcCell(trId, tcId);
    if (!c) return 'bg-slate-100';
    if (c.is_passing)              return 'bg-emerald-500';
    if (c.latest_run === 'fail')   return 'bg-red-400';
    if (c.latest_run === 'blocked') return 'bg-amber-400';
    if (c.latest_run === 'skipped') return 'bg-slate-300';
    return 'bg-blue-200'; // linked, no run yet
}
function trTcCellTitle(trId: number, tcId: number): string {
    const c = trTcCell(trId, tcId);
    if (!c) return 'Not linked';
    if (!c.latest_run) return 'Linked — no run logged';
    return `Last run: ${c.latest_run}`;
}
function trTcCellIcon(trId: number, tcId: number): string {
    const c = trTcCell(trId, tcId);
    if (!c || !c.latest_run) return '';
    const icons: Record<string, string> = { pass: '✓', fail: '✗', blocked: '!', skipped: '–' };
    return icons[c.latest_run] ?? '';
}

// --- Filtered matrix ---
const filteredMatrix = computed(() => {
    return props.matrix.filter(br => {
        if (filterStatus.value && br.status !== filterStatus.value) return false;
        if (filterCoverage.value === 'covered' && !br.is_covered) return false;
        if (filterCoverage.value === 'uncovered' && br.is_covered) return false;
        return true;
    });
});

// --- Gap data ---
const uncoveredTrs = computed<(RtmTr & { br_ref: string; br_title: string })[]>(() => {
    const result: (RtmTr & { br_ref: string; br_title: string })[] = [];
    for (const br of props.matrix) {
        if (br.trs.length === 0) continue;
        for (const tr of br.trs) {
            if (!tr.is_covered) {
                result.push({ ...tr, br_ref: br.ref, br_title: br.title });
            }
        }
    }
    return result;
});

const uncoveredBrs = computed(() => props.matrix.filter(br => !br.is_covered));
const brsWithNoTrs = computed(() => props.matrix.filter(br => br.trs.length === 0));

// Summary stats
const totalBrs = computed(() => props.matrix.length);
const coveredBrs = computed(() => props.matrix.filter(b => b.is_covered).length);
const totalTrs = computed(() => props.matrix.flatMap(b => b.trs).length);
const coveredTrs = computed(() => props.matrix.flatMap(b => b.trs).filter(t => t.is_covered).length);
const brCoverage = computed(() => totalBrs.value ? Math.round((coveredBrs.value / totalBrs.value) * 100) : 0);
const trCoverage = computed(() => totalTrs.value ? Math.round((coveredTrs.value / totalTrs.value) * 100) : 0);

function coverageColor(pct: number) {
    if (pct >= 80) return 'bg-emerald-500';
    if (pct >= 50) return 'bg-amber-400';
    return 'bg-red-500';
}
</script>

<template>
    <Head title="RTM" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Requirements Traceability Matrix</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Full BR → TR → Test Case traceability for {{ project.name }}.</p>
                </div>
            </div>

            <!-- Coverage summary cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-600">BR Coverage</span>
                        <span class="text-lg font-bold" :class="brCoverage >= 80 ? 'text-emerald-600' : brCoverage >= 50 ? 'text-amber-500' : 'text-red-500'">
                            {{ brCoverage }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all" :class="coverageColor(brCoverage)" :style="{ width: brCoverage + '%' }" />
                    </div>
                    <p class="text-xs text-slate-400 mt-2">{{ coveredBrs }} of {{ totalBrs }} business requirements covered</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-600">TR Coverage</span>
                        <span class="text-lg font-bold" :class="trCoverage >= 80 ? 'text-emerald-600' : trCoverage >= 50 ? 'text-amber-500' : 'text-red-500'">
                            {{ trCoverage }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all" :class="coverageColor(trCoverage)" :style="{ width: trCoverage + '%' }" />
                    </div>
                    <p class="text-xs text-slate-400 mt-2">{{ coveredTrs }} of {{ totalTrs }} technical requirements covered</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-1 border-b border-slate-200">
                <button v-for="tab in (['matrix', 'grid', 'graph', 'gaps'] as const)" :key="tab"
                    @click="activeTab = tab"
                    class="px-4 py-2 text-sm font-medium capitalize transition-colors"
                    :class="activeTab === tab
                        ? 'text-primary border-b-2 border-primary -mb-px'
                        : 'text-slate-500 hover:text-slate-700'">
                    {{ tab === 'gaps' ? 'Coverage Gaps' : tab === 'graph' ? 'Graph' : tab === 'grid' ? 'Waffle Grid' : 'Matrix' }}
                </button>
            </div>

            <!-- ── MATRIX TAB ── -->
            <template v-if="activeTab === 'matrix'">
                <!-- Filters -->
                <div class="flex items-center gap-3">
                    <select v-model="filterStatus"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">All statuses</option>
                        <option value="draft">Draft</option>
                        <option value="review">Review</option>
                        <option value="approved">Approved</option>
                        <option value="implemented">Implemented</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                    <select v-model="filterCoverage"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">All coverage</option>
                        <option value="covered">Covered only</option>
                        <option value="uncovered">Uncovered only</option>
                    </select>
                    <span class="text-xs text-slate-400 ml-auto">{{ filteredMatrix.length }} of {{ matrix.length }} BRs</span>
                </div>

                <!-- Matrix table -->
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div v-if="filteredMatrix.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                        No business requirements match the current filters.
                    </div>
                    <template v-else>
                        <div v-for="br in filteredMatrix" :key="br.id" class="border-b border-slate-100 last:border-0">
                            <!-- BR row -->
                            <div class="flex items-start gap-3 px-5 py-3 bg-slate-50 border-b border-slate-100">
                                <span class="font-mono text-xs text-slate-400 w-16 flex-shrink-0 pt-0.5">{{ br.ref }}</span>
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('projects.requirements.business.show', [project.id, br.id])"
                                        class="text-sm font-semibold text-slate-800 hover:text-primary line-clamp-1">
                                        {{ br.title }}
                                    </Link>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityClass[br.priority]">
                                        {{ br.priority_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass[br.status]">
                                        {{ br.status_label }}
                                    </span>
                                    <span v-if="br.is_covered" class="text-emerald-500 text-xs font-medium">✓ Covered</span>
                                    <span v-else class="text-red-400 text-xs font-medium">✗ Gap</span>
                                </div>
                            </div>

                            <!-- No TRs -->
                            <div v-if="br.trs.length === 0" class="px-5 py-3 pl-24 text-xs text-slate-400 italic">
                                No technical requirements linked.
                            </div>

                            <!-- TR rows -->
                            <div v-for="tr in br.trs" :key="tr.id" class="border-b border-slate-50 last:border-0">
                                <div class="flex items-start gap-3 px-5 py-2.5 pl-24">
                                    <span class="font-mono text-xs text-slate-400 w-16 flex-shrink-0 pt-0.5">{{ tr.ref }}</span>
                                    <div class="flex-1 min-w-0">
                                        <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])"
                                            class="text-sm text-slate-700 hover:text-primary line-clamp-1">
                                            {{ tr.title }}
                                        </Link>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="typeClass[tr.type]">
                                            {{ tr.type_label }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass[tr.status]">
                                            {{ tr.status_label }}
                                        </span>
                                        <span v-if="tr.is_covered" class="text-emerald-500 text-xs">✓</span>
                                        <span v-else class="text-red-400 text-xs">✗</span>
                                    </div>
                                </div>

                                <!-- No TCs -->
                                <div v-if="tr.test_cases.length === 0" class="px-5 py-2 pl-44 text-xs text-slate-400 italic">
                                    No test cases linked.
                                </div>

                                <!-- TC chips -->
                                <div v-else class="px-5 pb-3 pl-44 flex flex-wrap gap-1.5">
                                    <Link v-for="tc in tr.test_cases" :key="tc.id"
                                        :href="route('projects.test-cases.show', [project.id, tc.id])"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors hover:bg-slate-50"
                                        :class="tc.is_passing ? 'border-emerald-200 text-emerald-700' : 'border-slate-200 text-slate-600'">
                                        <span class="font-mono">{{ tc.ref }}</span>
                                        <span v-if="tc.latest_run"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs"
                                            :class="runClass[tc.latest_run]">
                                            {{ tc.latest_run }}
                                        </span>
                                        <span v-else class="text-slate-300">–</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- ── WAFFLE GRID TAB ── -->
            <template v-if="activeTab === 'grid'">

                <!-- Mode switcher -->
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">View:</span>
                    <div class="flex rounded-lg border border-slate-200 overflow-hidden text-xs font-medium">
                        <button
                            @click="gridMode = 'br-tr'"
                            class="px-3 py-1.5 transition-colors"
                            :class="gridMode === 'br-tr' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50'">
                            BR × TR
                        </button>
                        <button
                            @click="gridMode = 'tr-tc'"
                            class="px-3 py-1.5 border-l border-slate-200 transition-colors"
                            :class="gridMode === 'tr-tc' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50'">
                            TR × TC
                        </button>
                    </div>
                </div>

                <!-- ── BR × TR grid ── -->
                <template v-if="gridMode === 'br-tr'">
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <div class="flex items-center gap-4 px-5 py-3 border-b border-slate-100 bg-slate-50 text-xs text-slate-600">
                            <span class="font-medium text-slate-700">Legend:</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-emerald-500 inline-block"/> Covered</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-amber-400 inline-block"/> TCs linked, none passing</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-red-400 inline-block"/> No TCs linked</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-slate-100 border border-slate-200 inline-block"/> Not linked</span>
                        </div>
                        <div class="overflow-auto max-h-[70vh]">
                            <table class="text-xs border-collapse">
                                <thead class="sticky top-0 z-20 bg-white">
                                    <tr>
                                        <th class="sticky left-0 z-30 bg-white min-w-[220px] max-w-[220px] px-4 py-2 border-b border-r border-slate-200 text-left">
                                            <span class="text-slate-400 font-normal">BR \ TR</span>
                                        </th>
                                        <th v-for="tr in allTrs" :key="tr.id"
                                            class="border-b border-r border-slate-100 bg-white px-0 py-0 min-w-[52px] max-w-[52px]">
                                            <div class="flex flex-col items-center py-2 gap-1">
                                                <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])"
                                                    class="font-mono text-[10px] text-slate-500 hover:text-primary" :title="tr.title">
                                                    {{ tr.ref }}
                                                </Link>
                                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                    :class="tr.is_covered ? 'bg-emerald-400' : 'bg-red-300'" />
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="br in matrix" :key="br.id" class="hover:bg-slate-50/50 group">
                                        <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50/50 border-b border-r border-slate-100 px-4 py-2 min-w-[220px] max-w-[220px]">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                    :class="br.is_covered ? 'bg-emerald-400' : 'bg-red-300'" />
                                                <Link :href="route('projects.requirements.business.show', [project.id, br.id])"
                                                    class="hover:text-primary truncate font-medium text-slate-700">
                                                    <span class="font-mono text-slate-400 mr-1">{{ br.ref }}</span>{{ br.title }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td v-for="tr in allTrs" :key="tr.id"
                                            class="border-b border-r border-slate-100 text-center min-w-[52px] max-w-[52px] p-1.5">
                                            <div v-if="brTrCell(br.id, tr.id)"
                                                class="w-7 h-7 rounded-md mx-auto flex items-center justify-center cursor-default transition-transform hover:scale-110"
                                                :class="brTrCellClass(br.id, tr.id)"
                                                :title="brTrCellTitle(br.id, tr.id)">
                                                <span v-if="brTrCell(br.id, tr.id)!.is_covered" class="text-white text-[11px] font-bold">✓</span>
                                                <span v-else-if="brTrCell(br.id, tr.id)!.tc_count > 0" class="text-white text-[10px] font-bold">{{ brTrCell(br.id, tr.id)!.tc_count }}</span>
                                                <span v-else class="text-white text-[10px] font-bold">!</span>
                                            </div>
                                            <div v-else class="w-7 h-7 rounded-md mx-auto bg-slate-100" title="Not linked" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 text-xs text-slate-500 px-1">
                        <span>{{ matrix.length }} BRs × {{ allTrs.length }} TRs</span>
                        <span class="text-emerald-600 font-medium">{{ allTrs.filter(t => t.is_covered).length }} covered TRs</span>
                        <span class="text-red-400 font-medium">{{ allTrs.filter(t => !t.is_covered).length }} gaps</span>
                    </div>
                </template>

                <!-- ── TR × TC grid ── -->
                <template v-if="gridMode === 'tr-tc'">
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <div class="flex items-center gap-4 px-5 py-3 border-b border-slate-100 bg-slate-50 text-xs text-slate-600">
                            <span class="font-medium text-slate-700">Legend:</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-emerald-500 inline-block"/> Pass</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-red-400 inline-block"/> Fail</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-amber-400 inline-block"/> Blocked</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-slate-300 inline-block"/> Skipped</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-blue-200 inline-block"/> Linked, no run</span>
                            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-sm bg-slate-100 border border-slate-200 inline-block"/> Not linked</span>
                        </div>
                        <div class="overflow-auto max-h-[70vh]">
                            <table class="text-xs border-collapse">
                                <thead class="sticky top-0 z-20 bg-white">
                                    <tr>
                                        <th class="sticky left-0 z-30 bg-white min-w-[220px] max-w-[220px] px-4 py-2 border-b border-r border-slate-200 text-left">
                                            <span class="text-slate-400 font-normal">TR \ TC</span>
                                        </th>
                                        <th v-for="tc in allTcs" :key="tc.id"
                                            class="border-b border-r border-slate-100 bg-white px-0 py-0 min-w-[52px] max-w-[52px]">
                                            <div class="flex flex-col items-center py-2 gap-1">
                                                <Link :href="route('projects.test-cases.show', [project.id, tc.id])"
                                                    class="font-mono text-[10px] text-slate-500 hover:text-primary" :title="tc.title">
                                                    {{ tc.ref }}
                                                </Link>
                                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                    :class="tc.is_passing ? 'bg-emerald-400' : tc.latest_run ? 'bg-red-300' : 'bg-slate-200'" />
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tr in allTrs" :key="tr.id" class="hover:bg-slate-50/50 group">
                                        <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50/50 border-b border-r border-slate-100 px-4 py-2 min-w-[220px] max-w-[220px]">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                    :class="tr.is_covered ? 'bg-emerald-400' : 'bg-red-300'" />
                                                <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])"
                                                    class="hover:text-primary truncate font-medium text-slate-700">
                                                    <span class="font-mono text-slate-400 mr-1">{{ tr.ref }}</span>{{ tr.title }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td v-for="tc in allTcs" :key="tc.id"
                                            class="border-b border-r border-slate-100 text-center min-w-[52px] max-w-[52px] p-1.5">
                                            <div v-if="trTcCell(tr.id, tc.id)"
                                                class="w-7 h-7 rounded-md mx-auto flex items-center justify-center cursor-default transition-transform hover:scale-110"
                                                :class="trTcCellClass(tr.id, tc.id)"
                                                :title="trTcCellTitle(tr.id, tc.id)">
                                                <span class="text-white text-[11px] font-bold">{{ trTcCellIcon(tr.id, tc.id) }}</span>
                                            </div>
                                            <div v-else class="w-7 h-7 rounded-md mx-auto bg-slate-100" title="Not linked" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 text-xs text-slate-500 px-1">
                        <span>{{ allTrs.length }} TRs × {{ allTcs.length }} TCs</span>
                        <span class="text-emerald-600 font-medium">{{ allTcs.filter(tc => tc.is_passing).length }} passing TCs</span>
                        <span class="text-red-400 font-medium">{{ allTcs.filter(tc => !tc.is_passing).length }} not passing</span>
                    </div>
                </template>

            </template>

            <!-- ── GRAPH TAB ── -->
            <template v-if="activeTab === 'graph'">
                <RtmGraph :matrix="matrix" :project-id="project.id" />
            </template>

            <!-- ── GAPS TAB ── -->
            <template v-if="activeTab === 'gaps'">
                <div class="space-y-4">
                    <!-- BRs with no TRs -->
                    <div v-if="brsWithNoTrs.length" class="bg-white border border-red-100 rounded-xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-red-100 bg-red-50 flex items-center gap-2">
                            <span class="text-sm font-semibold text-red-700">BRs with no linked TRs</span>
                            <span class="ml-auto text-xs font-medium text-red-500 bg-red-100 px-2 py-0.5 rounded-full">{{ brsWithNoTrs.length }}</span>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="br in brsWithNoTrs" :key="br.id" class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs text-slate-400 w-20">{{ br.ref }}</td>
                                    <td class="px-5 py-3 text-slate-800">
                                        <Link :href="route('projects.requirements.business.show', [project.id, br.id])" class="hover:text-primary">
                                            {{ br.title }}
                                        </Link>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityClass[br.priority]">
                                            {{ br.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass[br.status]">
                                            {{ br.status_label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TRs with no passing test case -->
                    <div v-if="uncoveredTrs.length" class="bg-white border border-amber-100 rounded-xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-amber-100 bg-amber-50 flex items-center gap-2">
                            <span class="text-sm font-semibold text-amber-700">TRs with no passing test case</span>
                            <span class="ml-auto text-xs font-medium text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full">{{ uncoveredTrs.length }}</span>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">TR</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Title</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">From BR</th>
                                    <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Test Cases</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="tr in uncoveredTrs" :key="tr.id + '-' + tr.br_ref" class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs text-slate-400">{{ tr.ref }}</td>
                                    <td class="px-5 py-3 text-slate-800">
                                        <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])" class="hover:text-primary">
                                            {{ tr.title }}
                                        </Link>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs">
                                        <span class="font-mono">{{ tr.br_ref }}</span> {{ tr.br_title }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span v-if="tr.test_cases.length === 0" class="text-xs text-slate-400 italic">None linked</span>
                                        <span v-else class="text-xs text-amber-600">{{ tr.test_cases.length }} linked, none passing</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="brsWithNoTrs.length === 0 && uncoveredTrs.length === 0"
                        class="bg-emerald-50 border border-emerald-100 rounded-xl px-5 py-10 text-center">
                        <p class="text-emerald-700 font-semibold text-sm">No coverage gaps detected!</p>
                        <p class="text-emerald-500 text-xs mt-1">All business and technical requirements have passing test coverage.</p>
                    </div>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
