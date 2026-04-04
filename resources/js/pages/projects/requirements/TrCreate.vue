<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    types: SelectOption[];
    statuses: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Technical Requirements', href: route('projects.requirements.technical.index', props.project.id) },
    { title: 'New', href: '#' },
];

const form = useForm({
    title: '',
    description: '',
    type: 'functional',
    status: 'draft',
});

function submit() {
    form.post(route('projects.requirements.technical.store', props.project.id));
}
</script>

<template>
    <Head title="New Technical Requirement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-6">New Technical Requirement</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        placeholder="e.g. API endpoint should return 200 within 200ms"
                    />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"
                        placeholder="Detailed technical description..."
                    />
                </div>

                <!-- Type & Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                        <select v-model="form.type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select v-model="form.status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50"
                    >
                        Create Requirement
                    </button>
                    <Link
                        :href="route('projects.requirements.technical.index', project.id)"
                        class="px-5 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
