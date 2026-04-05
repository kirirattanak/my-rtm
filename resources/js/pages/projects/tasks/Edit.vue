<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    task: {
        id: number; title: string; description: string | null;
        sprint_id: number | null; effort_estimate: number | null;
        effort_unit: string; status: string; due_date: string | null;
        assignee_id: number | null; taskable_type: 'br' | 'tr' | null; taskable_id: number | null;
    };
    sprints: SelectOption[];
    members: SelectOption[];
    statuses: SelectOption[];
    effort_units: SelectOption[];
    linkable_brs: SelectOption[];
    linkable_trs: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Tasks', href: route('projects.tasks.index', props.project.id) },
    { title: props.task.title, href: route('projects.tasks.show', [props.project.id, props.task.id]) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    title:           props.task.title,
    description:     props.task.description ?? '',
    sprint_id:       props.task.sprint_id?.toString() ?? '',
    effort_estimate: props.task.effort_estimate?.toString() ?? '',
    effort_unit:     props.task.effort_unit,
    status:          props.task.status,
    due_date:        props.task.due_date ?? '',
    assignee_id:     props.task.assignee_id?.toString() ?? '',
    taskable_type:   props.task.taskable_type ?? '',
    taskable_id:     props.task.taskable_id?.toString() ?? '',
});

function submit() {
    form.patch(route('projects.tasks.update', [props.project.id, props.task.id]));
}
</script>

<template>
    <Head :title="`Edit — ${task.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-6">Edit Task</h1>
            <form @submit.prevent="submit" class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Title</label>
                    <input v-model="form.title" type="text" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.title }" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Effort Estimate</label>
                        <input v-model="form.effort_estimate" type="number" min="0" step="0.5"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Unit</label>
                        <select v-model="form.effort_unit"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="u in effort_units" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                        <select v-model="form.status"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Sprint</label>
                        <select v-model="form.sprint_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— No sprint —</option>
                            <option v-for="s in sprints" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Assignee</label>
                        <select v-model="form.assignee_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— Unassigned —</option>
                            <option v-for="m in members" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Due Date</label>
                        <input v-model="form.due_date" type="date"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Link type</label>
                        <select v-model="form.taskable_type"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— None —</option>
                            <option value="br">Business Requirement</option>
                            <option value="tr">Technical Requirement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Linked item</label>
                        <select v-model="form.taskable_id" :disabled="!form.taskable_type"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50">
                            <option value="">— Select —</option>
                            <template v-if="form.taskable_type === 'br'">
                                <option v-for="br in linkable_brs" :key="br.value" :value="br.value">{{ br.label }}</option>
                            </template>
                            <template v-if="form.taskable_type === 'tr'">
                                <option v-for="tr in linkable_trs" :key="tr.value" :value="tr.value">{{ tr.label }}</option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                        Save Changes
                    </button>
                    <a :href="route('projects.tasks.show', [project.id, task.id])"
                        class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
