<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SelectOption, type TestCase } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    tc: TestCase;
    linkable_trs: { id: number; ref: string; title: string }[];
    run_statuses: (SelectOption & { color: string })[];
    can: { edit: boolean; delete: boolean; log_run: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Cases', href: route('projects.test-cases.index', props.project.id) },
    { title: props.tc.ref, href: '#' },
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

const runBadge: Record<string, string> = {
    pass:    'bg-emerald-100 text-emerald-700',
    fail:    'bg-red-100 text-red-700',
    blocked: 'bg-amber-100 text-amber-700',
    skipped: 'bg-slate-100 text-slate-500',
};

const runForm = useForm({ status: '', notes: '' });
const linkForm = useForm({ technical_requirement_id: '' });

function logRun() {
    runForm.post(route('projects.test-cases.runs.store', [props.project.id, props.tc.id]), {
        onSuccess: () => runForm.reset(),
    });
}

function linkTr() {
    linkForm.post(route('projects.test-cases.tr-links.store', [props.project.id, props.tc.id]), {
        onSuccess: () => linkForm.reset(),
    });
}

function unlinkTr(trId: number) {
    router.delete(route('projects.test-cases.tr-links.destroy', [props.project.id, props.tc.id, trId]));
}

function confirmDelete() {
    if (confirm(`Delete "${props.tc.ref}"? This cannot be undone.`)) {
        router.delete(route('projects.test-cases.destroy', [props.project.id, props.tc.id]));
    }
}
</script>

<template>
    <Head :title="tc.ref" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-4xl stagger">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-mono text-sm text-slate-400">{{ tc.ref }}</span>
                        <span class="text-xs text-slate-400">{{ tc.type_label }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="priorityClass[tc.priority]">{{ tc.priority_label }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClass[tc.status]">{{ tc.status_label }}</span>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ tc.title }}</h1>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span v-if="tc.assignee">Assigned to {{ tc.assignee.name }}</span>
                        <span>Created by {{ tc.creator?.name }}</span>
                        <span>{{ new Date(tc.created_at).toLocaleDateString() }}</span>
                    </div>
                </div>
                <div v-if="can.edit || can.delete" class="flex items-center gap-2 flex-shrink-0">
                    <Link v-if="can.edit"
                        :href="route('projects.test-cases.edit', [project.id, tc.id])"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        Edit
                    </Link>
                    <button v-if="can.delete" @click="confirmDelete"
                        class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Description + Steps + Expected Result -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div v-if="tc.description">
                    <h2 class="text-sm font-medium text-slate-500 mb-1">Description</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ tc.description }}</p>
                </div>

                <div v-if="tc.steps.length">
                    <h2 class="text-sm font-medium text-slate-500 mb-2">Test Steps</h2>
                    <ol class="space-y-1">
                        <li v-for="(step, i) in tc.steps" :key="i"
                            class="flex gap-3 text-sm text-slate-700 bg-slate-50 rounded-lg px-3 py-2">
                            <span class="text-slate-400 font-mono text-xs w-5 flex-shrink-0 mt-0.5">{{ i + 1 }}.</span>
                            <span>{{ step }}</span>
                        </li>
                    </ol>
                </div>

                <div v-if="tc.expected_result">
                    <h2 class="text-sm font-medium text-slate-500 mb-1">Expected Result</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ tc.expected_result }}</p>
                </div>

                <p v-if="!tc.description && !tc.steps.length && !tc.expected_result"
                    class="text-sm text-slate-400">No details added yet.</p>
            </div>

            <!-- Linked TRs -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">Linked Technical Requirements</h2>
                    <form v-if="can.edit && linkable_trs.length" @submit.prevent="linkTr" class="flex gap-2">
                        <select v-model="linkForm.technical_requirement_id"
                            class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="" disabled>Select a TR to link…</option>
                            <option v-for="tr in linkable_trs" :key="tr.id" :value="tr.id">
                                {{ tr.ref }} — {{ tr.title }}
                            </option>
                        </select>
                        <button type="submit" :disabled="!linkForm.technical_requirement_id || linkForm.processing"
                            class="px-3 py-1.5 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                            Link
                        </button>
                    </form>
                    <p v-else-if="can.edit && !linkable_trs.length" class="text-xs text-slate-400">
                        All project TRs are already linked.
                    </p>
                </div>
                <div v-if="tc.technical_requirements.length === 0" class="px-5 py-4 text-sm text-slate-400">
                    No technical requirements linked yet.
                </div>
                <table v-else class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tr in tc.technical_requirements" :key="tr.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs text-slate-400 w-20">{{ tr.ref }}</td>
                            <td class="px-5 py-3 text-slate-800">
                                <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])"
                                    class="hover:text-primary">{{ tr.title }}</Link>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass[tr.status]">{{ tr.status_label }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button v-if="can.edit" @click="unlinkTr(tr.id)"
                                    class="text-xs text-red-400 hover:text-red-600">Unlink</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Log a Run -->
            <div v-if="can.log_run" class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Log Test Run</h2>
                <form @submit.prevent="logRun" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-slate-500 mb-1">Result</label>
                        <select v-model="runForm.status"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="" disabled>Select result…</option>
                            <option v-for="s in run_statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div class="flex-[2]">
                        <label class="block text-xs text-slate-500 mb-1">Notes (optional)</label>
                        <input v-model="runForm.notes" type="text"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            placeholder="Any observations…" />
                    </div>
                    <button type="submit" :disabled="!runForm.status || runForm.processing"
                        class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50 self-end">
                        Log Run
                    </button>
                </form>
            </div>

            <!-- Run History -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Run History</h2>
                </div>
                <div v-if="tc.runs.length === 0" class="px-5 py-4 text-sm text-slate-400">
                    No runs logged yet.
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Result</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Notes</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Executed by</th>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="run in tc.runs" :key="run.id">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="runBadge[run.status]">{{ run.status_label }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600 text-sm">{{ run.notes ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs">{{ run.executor?.name }}</td>
                            <td class="px-5 py-3 text-slate-400 text-xs">{{ new Date(run.created_at).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>
