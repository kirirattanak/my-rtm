<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Task } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    task: Task;
    can: { edit: boolean; delete: boolean; logHours: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Tasks', href: route('projects.tasks.index', props.project.id) },
    { title: props.task.title, href: '#' },
];

const statusClass: Record<string, string> = {
    todo:        'bg-slate-100 text-slate-500',
    in_progress: 'bg-blue-100 text-blue-700',
    done:        'bg-emerald-100 text-emerald-700',
    cancelled:   'bg-red-100 text-red-500',
};

const logForm = useForm({
    hours: '',
    notes: '',
});

function submitLog() {
    logForm.post(route('projects.tasks.logs.store', [props.project.id, props.task.id]), {
        onSuccess: () => logForm.reset(),
    });
}

function confirmDelete() {
    if (confirm(`Delete "${props.task.title}"? This cannot be undone.`)) {
        router.delete(route('projects.tasks.destroy', [props.project.id, props.task.id]));
    }
}

function taskableRoute(): string {
    if (!props.task.taskable) return '#';
    if (props.task.taskable_type === 'br') {
        return route('projects.requirements.business.show', [props.project.id, props.task.taskable.id]);
    }
    return route('projects.requirements.technical.show', [props.project.id, props.task.taskable.id]);
}
</script>

<template>
    <Head :title="task.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-3xl stagger">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClass[task.status]">
                            {{ task.status_label }}
                        </span>
                        <span v-if="task.sprint" class="text-xs text-slate-400">
                            in
                            <Link :href="route('projects.sprints.show', [project.id, task.sprint.id])"
                                class="hover:text-primary">{{ task.sprint.name }}</Link>
                        </span>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ task.title }}</h1>
                    <p class="text-xs text-slate-400">Created by {{ task.creator.name }} · {{ task.created_at }}</p>
                </div>
                <div v-if="can.edit || can.delete" class="flex items-center gap-2 flex-shrink-0">
                    <Link v-if="can.edit" :href="route('projects.tasks.edit', [project.id, task.id])"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">Edit</Link>
                    <button v-if="can.delete" @click="confirmDelete"
                        class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">Delete</button>
                </div>
            </div>

            <!-- Details grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Assignee</span>
                            <span class="text-slate-800 font-medium">{{ task.assignee?.name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Due date</span>
                            <span class="text-slate-800">{{ task.due_date ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Effort estimate</span>
                            <span class="text-slate-800">
                                <span v-if="task.effort_estimate">{{ task.effort_estimate }} {{ task.effort_unit_short }}</span>
                                <span v-else class="text-slate-300">—</span>
                            </span>
                        </div>
                        <div v-if="task.effort_unit === 'hours'" class="flex justify-between">
                            <span class="text-slate-500">Actual effort</span>
                            <span class="font-medium" :class="task.logged_hours > 0 ? 'text-primary' : 'text-slate-400'">
                                {{ task.effective_actual !== null ? task.effective_actual + ' hrs' : '—' }}
                                <span v-if="task.logged_hours > 0" class="text-xs text-slate-400 font-normal">(logged)</span>
                                <span v-else-if="task.status === 'done'" class="text-xs text-slate-400 font-normal">(estimated)</span>
                            </span>
                        </div>
                        <div v-if="task.completed_at" class="flex justify-between">
                            <span class="text-slate-500">Completed</span>
                            <span class="text-emerald-600">{{ task.completed_at }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Linked Requirement</h2>
                    <div v-if="task.taskable" class="text-sm">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold mr-1"
                            :class="task.taskable_type === 'br' ? 'bg-sky-100 text-sky-700' : 'bg-purple-100 text-purple-700'">
                            {{ task.taskable_type?.toUpperCase() }}
                        </span>
                        <a :href="taskableRoute()" class="font-mono text-xs text-slate-400 mr-1">{{ task.taskable.ref }}</a>
                        <a :href="taskableRoute()" class="text-slate-800 hover:text-primary">{{ task.taskable.title }}</a>
                    </div>
                    <p v-else class="text-sm text-slate-400 italic">Not linked to any requirement.</p>
                </div>
            </div>

            <!-- Description -->
            <div v-if="task.description" class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-medium text-slate-500 mb-2">Description</h2>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ task.description }}</p>
            </div>

            <!-- Log Hours -->
            <div v-if="can.logHours && task.effort_unit === 'hours'" class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Log Actual Hours</h2>
                <form @submit.prevent="submitLog" class="flex items-end gap-3">
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Hours</label>
                        <input v-model="logForm.hours" type="number" min="0.1" max="24" step="0.25" required placeholder="e.g. 2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': logForm.errors.hours }" />
                        <p v-if="logForm.errors.hours" class="text-xs text-red-500 mt-1">{{ logForm.errors.hours }}</p>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                        <input v-model="logForm.notes" type="text" placeholder="What did you work on?"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <button type="submit" :disabled="logForm.processing"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 flex-shrink-0">
                        Log Hours
                    </button>
                </form>

                <!-- Hours log history -->
                <div v-if="task.logs.length > 0" class="mt-5 border-t border-slate-100 pt-4">
                    <h3 class="text-xs font-medium text-slate-500 mb-3">Hours History</h3>
                    <div class="space-y-2">
                        <div v-for="log in task.logs" :key="log.id"
                            class="flex items-start gap-3 text-xs text-slate-600">
                            <span class="font-bold text-primary flex-shrink-0">{{ log.hours }}h</span>
                            <span class="flex-1">{{ log.notes ?? '—' }}</span>
                            <span class="text-slate-400 flex-shrink-0">{{ log.logger_name }} · {{ log.created_at }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-xs font-medium text-slate-700">
                        <span>Total logged</span>
                        <span class="text-primary">{{ task.logged_hours }}h</span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
