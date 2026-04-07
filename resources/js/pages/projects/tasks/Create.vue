<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import TaskLinkPicker from '@/components/TaskLinkPicker.vue';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    sprints: SelectOption[];
    members: SelectOption[];
    statuses: SelectOption[];
    priorities: SelectOption[];
    effort_units: SelectOption[];
    linkable_brs: SelectOption[];
    linkable_trs: SelectOption[];
    linkable_tcs: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Tasks', href: route('projects.tasks.index', props.project.id) },
    { title: 'New Task', href: '#' },
];

const form = useForm({
    title:           '',
    description:     '',
    sprint_id:       '',
    effort_estimate: '',
    effort_unit:     'points',
    status:          'todo',
    priority:        'medium',
    due_date:        '',
    assignee_id:     '',
    linked_br_ids:   [] as number[],
    linked_tr_ids:   [] as number[],
    linked_tc_ids:   [] as number[],
});

function submit() {
    form.post(route('projects.tasks.store', props.project.id));
}
</script>

<template>
    <Head title="New Task" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-6">New Task</h1>
            <form @submit.prevent="submit" class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Title</label>
                    <input v-model="form.title" type="text" required placeholder="Task title"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.title }" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                    <RichEditor v-model="form.description" placeholder="Optional description…" />
                </div>

                <!-- Effort -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Effort Estimate</label>
                        <input v-model="form.effort_estimate" type="number" min="0" step="0.5" placeholder="e.g. 3"
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

                <!-- Status + Priority -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                        <select v-model="form.status"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Priority</label>
                        <select v-model="form.priority"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Sprint + Assignee -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Sprint</label>
                        <select v-model="form.sprint_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— No sprint —</option>
                            <option v-for="s in sprints" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Assignee</label>
                        <select v-model="form.assignee_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— Unassigned —</option>
                            <option v-for="m in members" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Due date -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Due Date</label>
                    <input v-model="form.due_date" type="date"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                </div>

                <!-- Linked requirements -->
                <TaskLinkPicker
                    v-model:brIds="form.linked_br_ids"
                    v-model:trIds="form.linked_tr_ids"
                    v-model:tcIds="form.linked_tc_ids"
                    :linkable_brs="linkable_brs"
                    :linkable_trs="linkable_trs"
                    :linkable_tcs="linkable_tcs" />

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                        Create Task
                    </button>
                    <a :href="route('projects.tasks.index', project.id)"
                        class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
