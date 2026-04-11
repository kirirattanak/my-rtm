<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TestSuiteDetail, type TestRunStatus } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    suite: TestSuiteDetail;
    can: { delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Suites', href: route('projects.test-suites.index', props.project.id) },
    { title: props.suite.name, href: '#' },
];

const expandedTcs = ref<Set<number>>(new Set());

function toggleTc(id: number) {
    if (expandedTcs.value.has(id)) {
        expandedTcs.value.delete(id);
    } else {
        expandedTcs.value.add(id);
    }
}

function deleteSuite() {
    if (!confirm('Delete this test suite?')) return;
    router.delete(route('projects.test-suites.destroy', [props.project.id, props.suite.id]));
}

const runClass: Record<string, string> = {
    pass:    'bg-emerald-100 text-emerald-700',
    fail:    'bg-red-100 text-red-700',
    blocked: 'bg-amber-100 text-amber-700',
    skipped: 'bg-slate-100 text-slate-500',
    not_run: 'bg-slate-100 text-slate-400',
};

const runLabel: Record<string, string> = {
    pass:    'Pass',
    fail:    'Fail',
    blocked: 'Blocked',
    skipped: 'Skipped',
    not_run: 'Not Run',
};

function passRate(summary: TestSuiteDetail['summary']): number {
    if (summary.total === 0) return 0;
    return Math.round((summary.pass / summary.total) * 100);
}
</script>

<template>
    <Head :title="suite.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ suite.name }}</h1>
                    <p v-if="suite.description" class="text-sm text-slate-500 mt-0.5">{{ suite.description }}</p>
                    <p class="text-xs text-slate-400 mt-1">Created by {{ suite.creator }} · {{ suite.created_at }}</p>
                </div>
                <button
                    v-if="can.delete"
                    @click="deleteSuite"
                    class="text-sm text-red-500 hover:underline"
                >
                    Delete Suite
                </button>
            </div>

            <!-- Summary bar -->
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-700">Overall Result</h2>
                    <span class="text-sm font-semibold" :class="passRate(suite.summary) >= 80 ? 'text-emerald-600' : passRate(suite.summary) >= 50 ? 'text-amber-500' : 'text-red-500'">
                        {{ passRate(suite.summary) }}% pass
                    </span>
                </div>
                <div class="flex rounded-full overflow-hidden h-3 gap-px">
                    <div v-if="suite.summary.pass"    :style="{ width: (suite.summary.pass    / suite.summary.total * 100) + '%' }" class="bg-emerald-500" />
                    <div v-if="suite.summary.fail"    :style="{ width: (suite.summary.fail    / suite.summary.total * 100) + '%' }" class="bg-red-400" />
                    <div v-if="suite.summary.blocked" :style="{ width: (suite.summary.blocked / suite.summary.total * 100) + '%' }" class="bg-amber-400" />
                    <div v-if="suite.summary.skipped" :style="{ width: (suite.summary.skipped / suite.summary.total * 100) + '%' }" class="bg-slate-300" />
                    <div v-if="suite.summary.not_run" :style="{ width: (suite.summary.not_run / suite.summary.total * 100) + '%' }" class="bg-slate-100 border border-slate-200" />
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-slate-600 flex-wrap">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"/> Pass: {{ suite.summary.pass }}</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"/> Fail: {{ suite.summary.fail }}</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"/> Blocked: {{ suite.summary.blocked }}</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300 inline-block"/> Skipped: {{ suite.summary.skipped }}</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-200 border border-slate-300 inline-block"/> Not Run: {{ suite.summary.not_run }}</span>
                    <span class="ml-auto text-slate-400">{{ suite.summary.total }} total</span>
                </div>
            </div>

            <!-- No BRs -->
            <div v-if="suite.brs.length === 0" class="text-center py-12 text-slate-400">
                <p>No business requirements in this suite.</p>
            </div>

            <!-- BR → TR → TC tree -->
            <div v-else class="space-y-4">
                <div
                    v-for="br in suite.brs"
                    :key="br.id"
                    class="bg-white rounded-xl border border-slate-200 overflow-hidden"
                >
                    <!-- BR header -->
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                        <span class="text-xs font-mono text-slate-400 mr-2">{{ br.ref }}</span>
                        <span class="text-sm font-semibold text-slate-800">{{ br.title }}</span>
                    </div>

                    <!-- TRs -->
                    <div v-if="br.trs.length === 0" class="px-5 py-3 text-sm text-slate-400">
                        No technical requirements linked.
                    </div>

                    <div v-else v-for="tr in br.trs" :key="tr.id">
                        <!-- TR row -->
                        <div class="px-5 py-2.5 pl-10 border-b border-slate-100 bg-white">
                            <span class="text-xs font-mono text-slate-400 mr-2">{{ tr.ref }}</span>
                            <span class="text-sm text-slate-700">{{ tr.title }}</span>
                        </div>

                        <!-- TCs -->
                        <div v-if="tr.test_cases.length === 0" class="px-5 py-2 pl-16 text-xs text-slate-400">
                            No test cases linked.
                        </div>

                        <div v-else v-for="tc in tr.test_cases" :key="tc.id">
                            <!-- TC row -->
                            <div
                                class="flex items-center gap-3 px-5 py-2.5 pl-16 border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition"
                                @click="toggleTc(tc.id)"
                            >
                                <span class="text-xs font-mono text-slate-400">{{ tc.ref }}</span>
                                <span class="text-sm text-slate-800 flex-1">{{ tc.title }}</span>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="runClass[tc.latest_run ?? 'not_run']"
                                >
                                    {{ runLabel[tc.latest_run ?? 'not_run'] }}
                                </span>
                                <span class="text-slate-300 text-xs">
                                    {{ expandedTcs.has(tc.id) ? '▲' : '▼' }} {{ tc.runs.length }} run{{ tc.runs.length !== 1 ? 's' : '' }}
                                </span>
                            </div>

                            <!-- Run log (expanded) -->
                            <div v-if="expandedTcs.has(tc.id)" class="pl-20 pr-5 py-2 bg-slate-50 border-b border-slate-100">
                                <div v-if="tc.runs.length === 0" class="text-xs text-slate-400 py-2">No runs yet.</div>
                                <table v-else class="w-full text-xs">
                                    <thead>
                                        <tr class="text-slate-400">
                                            <th class="text-left pb-1 pr-4 font-medium">Status</th>
                                            <th class="text-left pb-1 pr-4 font-medium">Executor</th>
                                            <th class="text-left pb-1 pr-4 font-medium">Notes</th>
                                            <th class="text-left pb-1 font-medium">When</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="run in tc.runs" :key="run.id">
                                            <td class="py-1.5 pr-4">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="runClass[run.status]">
                                                    {{ run.status_label }}
                                                </span>
                                            </td>
                                            <td class="py-1.5 pr-4 text-slate-600">{{ run.executor }}</td>
                                            <td class="py-1.5 pr-4 text-slate-500">{{ run.notes ?? '—' }}</td>
                                            <td class="py-1.5 text-slate-400">{{ run.created_at }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
