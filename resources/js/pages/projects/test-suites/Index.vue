<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TestSuiteListItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    suites: TestSuiteListItem[];
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Suites', href: '#' },
];

function deleteSuite(id: number) {
    if (!confirm('Delete this test suite?')) return;
    router.delete(route('projects.test-suites.destroy', [props.project.id, id]));
}
</script>

<template>
    <Head title="Test Suites" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Test Suites</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('projects.test-suites.create', project.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition"
                >
                    + New Suite
                </Link>
            </div>

            <div v-if="suites.length === 0" class="text-center py-16 text-slate-400">
                <p class="text-lg font-medium">No test suites yet</p>
                <p v-if="can.create" class="text-sm mt-1">Create one to group test cases by business requirements.</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">BRs</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Created by</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="suite in suites" :key="suite.id" class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('projects.test-suites.show', [project.id, suite.id])"
                                    class="font-medium text-slate-800 hover:text-primary transition"
                                >
                                    {{ suite.name }}
                                </Link>
                                <p v-if="suite.description" class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">
                                    {{ suite.description }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ suite.br_count }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ suite.creator }}</td>
                            <td class="px-4 py-3 text-slate-400 text-xs">{{ suite.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    @click="deleteSuite(suite.id)"
                                    class="text-xs text-red-500 hover:underline"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
