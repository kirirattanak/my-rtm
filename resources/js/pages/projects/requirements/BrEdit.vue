<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    br: {
        id: number;
        ref: string;
        title: string;
        description: string | null;
        priority: string;
        status: string;
        category: string | null;
        tags: string[];
    };
    priorities: SelectOption[];
    statuses: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
    { title: props.br.ref, href: route('projects.requirements.business.show', [props.project.id, props.br.id]) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    title: props.br.title,
    description: props.br.description ?? '',
    priority: props.br.priority,
    status: props.br.status,
    category: props.br.category ?? '',
    tags: [...props.br.tags],
});

const tagInput = ref('');

function addTag() {
    const tag = tagInput.value.trim();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }
    tagInput.value = '';
}

function removeTag(tag: string) {
    form.tags = form.tags.filter(t => t !== tag);
}

function submit() {
    form.patch(route('projects.requirements.business.update', [props.project.id, props.br.id]));
}
</script>

<template>
    <Head :title="`Edit ${br.ref}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-1">Edit {{ br.ref }}</h1>
            <p class="text-sm text-slate-500 mb-6">{{ br.title }}</p>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <RichEditor v-model="form.description" />
                </div>

                <!-- Priority & Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
                        <select v-model="form.priority" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select v-model="form.status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <input
                        v-model="form.category"
                        type="text"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tags</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span
                            v-for="tag in form.tags"
                            :key="tag"
                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full"
                        >
                            {{ tag }}
                            <button type="button" @click="removeTag(tag)" class="hover:text-primary/60">&times;</button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input
                            v-model="tagInput"
                            type="text"
                            class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            placeholder="Type a tag and press Enter"
                            @keydown.enter.prevent="addTag"
                        />
                        <button type="button" @click="addTag" class="px-3 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50">Add</button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                    <Link
                        :href="route('projects.requirements.business.show', [project.id, br.id])"
                        class="px-5 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
