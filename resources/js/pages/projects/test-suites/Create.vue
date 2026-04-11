<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    brs: { id: number; ref: string; title: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Suites', href: route('projects.test-suites.index', props.project.id) },
    { title: 'New Suite', href: '#' },
];

const form = useForm({
    name:        '',
    description: '',
    br_ids:      [] as number[],
});

function toggleBr(id: number) {
    const idx = form.br_ids.indexOf(id);
    if (idx === -1) {
        form.br_ids.push(id);
    } else {
        form.br_ids.splice(idx, 1);
    }
}

function submit() {
    form.post(route('projects.test-suites.store', props.project.id));
}
</script>

<template>
    <Head title="New Test Suite" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">New Test Suite</h1>
                <p class="text-sm text-slate-500 mt-0.5">Select business requirements to include all their linked test cases.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        placeholder="e.g. Sprint 3 Regression"
                    />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <RichEditor v-model="form.description" placeholder="Optional description…" />
                </div>

                <!-- BR selection -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Business Requirements <span class="text-red-500">*</span>
                        <span class="text-slate-400 font-normal ml-1">({{ form.br_ids.length }} selected)</span>
                    </label>

                    <div v-if="brs.length === 0" class="text-sm text-slate-400 py-4 text-center border border-dashed border-slate-200 rounded-lg">
                        No business requirements in this project yet.
                    </div>

                    <div v-else class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-72 overflow-y-auto">
                        <label
                            v-for="br in brs"
                            :key="br.id"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 cursor-pointer transition"
                        >
                            <input
                                type="checkbox"
                                :value="br.id"
                                :checked="form.br_ids.includes(br.id)"
                                @change="toggleBr(br.id)"
                                class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary"
                            />
                            <div>
                                <span class="text-xs font-mono text-slate-400 mr-1">{{ br.ref }}</span>
                                <span class="text-sm text-slate-800">{{ br.title }}</span>
                            </div>
                        </label>
                    </div>
                    <p v-if="form.errors.br_ids" class="text-xs text-red-500 mt-1">{{ form.errors.br_ids }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50"
                    >
                        Create Suite
                    </button>
                    <a
                        :href="route('projects.test-suites.index', project.id)"
                        class="text-sm text-slate-500 hover:text-slate-700"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
