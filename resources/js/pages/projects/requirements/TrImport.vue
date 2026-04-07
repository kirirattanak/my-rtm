<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CsvImport from '@/components/CsvImport.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    result: { imported: number; skipped: { row: number; reason: string }[] } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Technical Requirements', href: route('projects.requirements.technical.index', props.project.id) },
    { title: 'Import' },
];

const columns = [
    { name: 'title',       required: true,  example: 'JWT token validation' },
    { name: 'description', required: false, example: 'Tokens must expire after 24 hours' },
    { name: 'type',        required: false, values: 'functional · non_functional · constraint', example: 'functional' },
    { name: 'status',      required: false, values: 'draft · review · approved · implemented · deprecated', example: 'draft' },
];

const form = useForm({ file: null as File | null });
const error = ref<string | undefined>();

function handleSubmit(file: File) {
    form.file = file;
    error.value = undefined;
    form.post(route('projects.requirements.technical.import.store', props.project.id), {
        forceFormData: true,
        onError: (errors) => { error.value = errors.file ?? 'Import failed.'; },
    });
}
</script>

<template>
    <Head title="Import Technical Requirements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl space-y-2">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-slate-900">Import Technical Requirements</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
            </div>

            <CsvImport
                :columns="columns"
                template-filename="tr-template.csv"
                :result="result"
                :processing="form.processing"
                :error="error"
                @submit="handleSubmit"
            />
        </div>
    </AppLayout>
</template>
