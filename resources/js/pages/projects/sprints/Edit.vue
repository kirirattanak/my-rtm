<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    sprint: { id: number; name: string; start_date: string; end_date: string; capacity: number | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Sprints', href: route('projects.sprints.index', props.project.id) },
    { title: props.sprint.name, href: route('projects.sprints.show', [props.project.id, props.sprint.id]) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    name:       props.sprint.name,
    start_date: props.sprint.start_date,
    end_date:   props.sprint.end_date,
    capacity:   props.sprint.capacity?.toString() ?? '',
});

function submit() {
    form.patch(route('projects.sprints.update', [props.project.id, props.sprint.id]));
}
</script>

<template>
    <Head :title="`Edit ${sprint.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-6">Edit Sprint</h1>
            <form @submit.prevent="submit" class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sprint Name</label>
                    <input v-model="form.name" type="text" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.name }" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Start Date</label>
                        <input v-model="form.start_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">End Date</label>
                        <input v-model="form.end_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Capacity (hours, optional)</label>
                    <input v-model="form.capacity" type="number" min="0" step="0.5"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                        Save Changes
                    </button>
                    <a :href="route('projects.sprints.show', [project.id, sprint.id])"
                        class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
