<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem, type Project, type ProjectStatus } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: Pick<Project, 'id' | 'name' | 'description' | 'status' | 'start_date' | 'target_date'>;
    statuses: { value: ProjectStatus; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Edit', href: route('projects.edit', props.project.id) },
];

const form = useForm({
    name:        props.project.name,
    description: props.project.description ?? '',
    status:      props.project.status,
    start_date:  props.project.start_date ?? '',
    target_date: props.project.target_date ?? '',
});

function submit() {
    form.patch(route('projects.update', props.project.id));
}
</script>

<template>
    <Head :title="`Edit · ${project.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-slate-900">Edit Project</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Project Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.name }" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none" />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select v-model="form.status"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                            <input v-model="form.start_date" type="date"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                :class="{ 'border-red-400': form.errors.start_date }" />
                            <InputError :message="form.errors.start_date" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Target Date</label>
                            <input v-model="form.target_date" type="date"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                :class="{ 'border-red-400': form.errors.target_date }" />
                            <InputError :message="form.errors.target_date" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="bg-primary text-primary-foreground text-sm font-medium px-5 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                            Save Changes
                        </button>
                        <a :href="route('projects.show', project.id)"
                            class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
