<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import { type BreadcrumbItem, type SelectOption, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    members: Pick<User, 'id' | 'name'>[];
    tc: {
        id: number; ref: string; title: string; description: string | null;
        steps: string[]; expected_result: string | null;
        type: string; priority: string; status: string; assignee_id: number | null;
    };
    types: SelectOption[];
    priorities: SelectOption[];
    statuses: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Test Cases', href: route('projects.test-cases.index', props.project.id) },
    { title: props.tc.ref, href: route('projects.test-cases.show', [props.project.id, props.tc.id]) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    title: props.tc.title,
    description: props.tc.description ?? '',
    steps: [...props.tc.steps],
    expected_result: props.tc.expected_result ?? '',
    type: props.tc.type,
    priority: props.tc.priority,
    status: props.tc.status,
    assignee_id: props.tc.assignee_id ?? ('' as string | number),
});

const newStep = ref('');

function addStep() {
    const s = newStep.value.trim();
    if (s) form.steps.push(s);
    newStep.value = '';
}

function removeStep(i: number) {
    form.steps.splice(i, 1);
}

function submit() {
    form.patch(route('projects.test-cases.update', [props.project.id, props.tc.id]));
}
</script>

<template>
    <Head :title="`Edit ${tc.ref}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-1">Edit {{ tc.ref }}</h1>
            <p class="text-sm text-slate-500 mb-6">{{ tc.title }}</p>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <RichEditor v-model="form.description" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Test Steps</label>
                    <ol v-if="form.steps.length" class="mb-2 space-y-1">
                        <li v-for="(step, i) in form.steps" :key="i"
                            class="flex items-start gap-2 bg-slate-50 rounded-lg px-3 py-2 text-sm">
                            <span class="text-slate-400 font-mono text-xs mt-0.5 w-5 flex-shrink-0">{{ i + 1 }}.</span>
                            <span class="flex-1 text-slate-700">{{ step }}</span>
                            <button type="button" @click="removeStep(i)" class="text-slate-300 hover:text-red-400 text-xs">✕</button>
                        </li>
                    </ol>
                    <div class="flex gap-2">
                        <input v-model="newStep" type="text"
                            class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            placeholder="Add a step and press Enter"
                            @keydown.enter.prevent="addStep" />
                        <button type="button" @click="addStep"
                            class="px-3 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50">Add</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expected Result</label>
                    <textarea v-model="form.expected_result" rows="2"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                        <select v-model="form.type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
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

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Assignee</label>
                    <select v-model="form.assignee_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">Unassigned</option>
                        <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                        Save Changes
                    </button>
                    <Link :href="route('projects.test-cases.show', [project.id, tc.id])"
                        class="px-5 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
