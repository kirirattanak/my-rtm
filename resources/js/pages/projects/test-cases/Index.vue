<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TestCaseListItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    tcs: TestCaseListItem[];
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Cases', href: route('projects.test-cases.index', props.project.id) },
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
                <Link
                    v-if="can.create"
                    :href="route('projects.test-cases.create', project.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition"
                >
                    + New TC
                </Link>
            </div>

            <div v-if="tcs.length === 0" class="text-center py-16 text-slate-400">
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
                        <tr
                            v-for="tc in tcs"
                            :key="tc.id"
                            class="hover:bg-slate-50 transition cursor-pointer"
                            @click="$inertia.visit(route('projects.test-cases.show', [project.id, tc.id]))"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ tc.ref }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ tc.title }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                <span>{{ typeIcon[tc.type] }} {{ tc.type_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="priorityClass[tc.priority]">
                                    {{ tc.priority_label }}
                                </span>
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
            </div>
        </div>
    </AppLayout>
</template>
