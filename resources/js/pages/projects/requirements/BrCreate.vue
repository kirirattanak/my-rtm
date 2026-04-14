<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    priorities: SelectOption[];
    statuses: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
    { title: 'New', href: '#' },
];

const form = useForm({
    title: '',
    description: '',
    priority: 'medium',
    status: 'draft',
    category: '',
    tags: [] as string[],
    optimistic_hours: null as number | null,
    most_likely_hours: null as number | null,
    pessimistic_hours: null as number | null,
});

const tagInput = ref('');
const showPert = ref(false);

const pertExpected = computed(() => {
    const o = Number(form.optimistic_hours);
    const m = Number(form.most_likely_hours);
    const p = Number(form.pessimistic_hours);
    if (!form.optimistic_hours && !form.most_likely_hours && !form.pessimistic_hours) return null;
    if (isNaN(o) || isNaN(m) || isNaN(p)) return null;
    return ((o + 4 * m + p) / 6).toFixed(1);
});

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
    form.post(route('projects.requirements.business.store', props.project.id));
}
</script>

<template>
    <Head title="New Business Requirement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl animate-in">
            <h1 class="text-xl font-semibold text-slate-900 mb-6">New Business Requirement</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        placeholder="e.g. User should be able to log in with email"
                    />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <RichEditor v-model="form.description" placeholder="Detailed description of the requirement…" />
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
                        placeholder="e.g. Authentication, Reporting"
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

                <!-- PERT Estimates -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 transition"
                        @click="showPert = !showPert"
                    >
                        <span>Effort Estimate (PERT)</span>
                        <span class="flex items-center gap-2">
                            <span v-if="pertExpected" class="text-xs font-normal text-primary">Expected: {{ pertExpected }} h</span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': showPert }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div v-if="showPert" class="px-4 py-4 space-y-4">
                        <p class="text-xs text-slate-500">Provide three hour estimates. The PERT expected value (O + 4M + P) / 6 is used for sprint capacity planning.</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Optimistic (h)</label>
                                <input v-model.number="form.optimistic_hours" type="number" min="0" step="0.5"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    placeholder="e.g. 4" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Most Likely (h)</label>
                                <input v-model.number="form.most_likely_hours" type="number" min="0" step="0.5"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    placeholder="e.g. 8" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Pessimistic (h)</label>
                                <input v-model.number="form.pessimistic_hours" type="number" min="0" step="0.5"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    placeholder="e.g. 16" />
                            </div>
                        </div>
                        <div v-if="pertExpected" class="text-sm text-slate-600">
                            Expected: <span class="font-semibold text-primary">{{ pertExpected }} h</span>
                        </div>
                        <p v-if="form.errors.optimistic_hours" class="text-xs text-red-500">{{ form.errors.optimistic_hours }}</p>
                        <p v-if="form.errors.most_likely_hours" class="text-xs text-red-500">{{ form.errors.most_likely_hours }}</p>
                        <p v-if="form.errors.pessimistic_hours" class="text-xs text-red-500">{{ form.errors.pessimistic_hours }}</p>
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
                        :href="route('projects.requirements.business.index', project.id)"
                        class="px-5 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
