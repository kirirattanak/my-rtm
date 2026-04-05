<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { type BreadcrumbItem, type BrListItem, type Paginator } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    brs: Paginator<BrListItem>;
    can: { create: boolean };
}>();

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
                <Link v-if="can.create"
                    :href="route('projects.requirements.business.create', project.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition">
                    + New BR
                </Link>
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
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Created by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="br in brs.data" :key="br.id"
                            class="hover:bg-slate-50 transition cursor-pointer"
                            @click="$inertia.visit(route('projects.requirements.business.show', [project.id, br.id]))">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ br.ref }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ br.title }}</td>
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
                            <td class="px-4 py-3 text-slate-500">{{ br.creator?.name }}</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :paginator="brs" class="px-4" />
            </div>
        </div>
    </AppLayout>
</template>
