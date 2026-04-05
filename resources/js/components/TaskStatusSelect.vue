<script setup lang="ts">
import { type TaskListItem } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    task: TaskListItem;
    projectId: number;
}>();

const statusOptions = [
    { value: 'todo',        label: 'To Do' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'done',        label: 'Done' },
    { value: 'cancelled',   label: 'Cancelled' },
];

const statusClass: Record<string, string> = {
    todo:        'bg-slate-100 text-slate-600 border-slate-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    done:        'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled:   'bg-red-50 text-red-500 border-red-200',
};

function update(event: Event) {
    const status = (event.target as HTMLSelectElement).value;
    router.patch(
        route('projects.tasks.status.update', [props.projectId, props.task.id]),
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <select
        :value="task.status"
        @change="update"
        class="text-xs font-medium rounded-full px-2 py-0.5 border cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors"
        :class="statusClass[task.status]">
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
        </option>
    </select>
</template>
