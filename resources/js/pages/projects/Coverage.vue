<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type CoverageSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    summary: CoverageSummary;
    trs: {
        id: number; ref: string; title: string;
        status: string; status_label: string;
        tc_count: number; covered: boolean;
        brs: { id: number; ref: string }[];
    }[];
    brs: {
        id: number; ref: string; title: string;
        tr_count: number; covered: boolean;
        status: string; status_label: string;
    }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Coverage', href: '#' },
];

function gaugeColor(pct: number) {
    if (pct >= 80) return 'text-emerald-600';
    if (pct >= 50) return 'text-amber-500';
    return 'text-red-500';
}

function gaugeBg(pct: number) {
    if (pct >= 80) return 'bg-emerald-500';
    if (pct >= 50) return 'bg-amber-400';
    return 'bg-red-500';
}
</script>

<template>
    <Head title="Coverage" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">

            <div>
                <h1 class="text-xl font-semibold text-slate-900">Coverage Report</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <!-- TR Coverage -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">TR Coverage</p>
                            <p class="text-3xl font-bold mt-1" :class="gaugeColor(summary.tr_coverage)">
                                {{ summary.tr_coverage }}%
                            </p>
                        </div>
                        <div class="text-right text-sm text-slate-500">
                            <p>{{ summary.covered_trs }} / {{ summary.total_trs }}</p>
                            <p class="text-xs text-slate-400">TRs with passing tests</p>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                            :class="gaugeBg(summary.tr_coverage)"
                            :style="{ width: summary.tr_coverage + '%' }" />
                    </div>
                </div>

                <!-- BR Coverage -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">BR Coverage</p>
                            <p class="text-3xl font-bold mt-1" :class="gaugeColor(summary.br_coverage)">
                                {{ summary.br_coverage }}%
                            </p>
                        </div>
                        <div class="text-right text-sm text-slate-500">
                            <p>{{ summary.covered_brs }} / {{ summary.total_brs }}</p>
                            <p class="text-xs text-slate-400">BRs fully covered</p>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                            :class="gaugeBg(summary.br_coverage)"
                            :style="{ width: summary.br_coverage + '%' }" />
                    </div>
                </div>
            </div>

            <!-- TR breakdown -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Technical Requirements</h2>
                    <Link :href="route('projects.requirements.technical.index', project.id)"
                        class="text-xs text-primary hover:underline">View all →</Link>
                </div>
                <div v-if="trs.length === 0" class="px-5 py-4 text-sm text-slate-400">
                    No technical requirements found.
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-8"></th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Ref</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Title</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Test Cases</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Traced from</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tr in trs" :key="tr.id" :class="!tr.covered ? 'bg-red-50/30' : ''">
                            <td class="px-5 py-3 text-center">
                                <span v-if="tr.covered" class="text-emerald-500 text-sm">✓</span>
                                <span v-else class="text-red-400 text-sm">✗</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">
                                <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])"
                                    class="hover:text-primary">{{ tr.ref }}</Link>
                            </td>
                            <td class="px-5 py-3 text-slate-800">{{ tr.title }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs">
                                <Link :href="route('projects.test-cases.index', project.id)"
                                    class="hover:text-primary">
                                    {{ tr.tc_count }} TC{{ tr.tc_count !== 1 ? 's' : '' }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Link v-for="br in tr.brs" :key="br.id"
                                        :href="route('projects.requirements.business.show', [project.id, br.id])"
                                        class="font-mono text-xs px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded hover:bg-primary/10 hover:text-primary">
                                        {{ br.ref }}
                                    </Link>
                                    <span v-if="!tr.brs.length" class="text-slate-300 text-xs">—</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- BR breakdown -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Business Requirements</h2>
                    <Link :href="route('projects.requirements.business.index', project.id)"
                        class="text-xs text-primary hover:underline">View all →</Link>
                </div>
                <div v-if="brs.length === 0" class="px-5 py-4 text-sm text-slate-400">No business requirements found.</div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 w-8"></th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Ref</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Title</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Linked TRs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="br in brs" :key="br.id" :class="!br.covered ? 'bg-red-50/30' : ''">
                            <td class="px-5 py-3 text-center">
                                <span v-if="br.covered" class="text-emerald-500 text-sm">✓</span>
                                <span v-else-if="br.tr_count === 0" class="text-slate-300 text-sm" title="No TRs linked">—</span>
                                <span v-else class="text-red-400 text-sm">✗</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">
                                <Link :href="route('projects.requirements.business.show', [project.id, br.id])"
                                    class="hover:text-primary">{{ br.ref }}</Link>
                            </td>
                            <td class="px-5 py-3 text-slate-800">{{ br.title }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs">{{ br.tr_count }} TR{{ br.tr_count !== 1 ? 's' : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>
