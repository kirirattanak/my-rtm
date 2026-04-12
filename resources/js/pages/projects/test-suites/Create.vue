<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import RichEditor from '@/components/RichEditor.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    brs: { id: number; ref: string; title: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
    { title: 'Test Suites', href: route('projects.test-suites.index', { project: props.project.id }) },
    { title: 'New Suite', href: '#' },
];

const form = useForm({
    name:        '',
    description: '',
    br_ids:      [] as number[],
});

// ── BR picker ────────────────────────────────────────────────────
const search    = ref('');
const selectVal = ref('');

const filteredBrs = computed(() => {
    const q = search.value.toLowerCase();
    return props.brs
        .filter(br => !form.br_ids.includes(br.id))
        .filter(br => !q || br.title.toLowerCase().includes(q) || br.ref.toLowerCase().includes(q));
});

function addBr() {
    if (!selectVal.value) return;
    form.br_ids.push(Number(selectVal.value));
    selectVal.value = '';
    search.value = '';
}

function removeBr(id: number) {
    form.br_ids = form.br_ids.filter(x => x !== id);
}

function labelFor(id: number): string {
    const br = props.brs.find(b => b.id === id);
    return br ? `${br.ref} — ${br.title}` : String(id);
}

function submit() {
    form.post(route('projects.test-suites.store', { project: props.project.id }));
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
                    <input v-model="form.name" type="text"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        placeholder="e.g. Sprint 3 Regression" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <RichEditor v-model="form.description" placeholder="Optional description…" />
                </div>

                <!-- BR picker -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Business Requirements <span class="text-red-500">*</span>
                        <span class="text-slate-400 font-normal ml-1">({{ form.br_ids.length }} selected)</span>
                    </label>

                    <!-- Selected pills -->
                    <div v-if="form.br_ids.length" class="flex flex-wrap gap-1.5 mb-3">
                        <span v-for="id in form.br_ids" :key="id"
                            class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">
                            <span class="max-w-[260px] truncate">{{ labelFor(id) }}</span>
                            <button type="button" @click="removeBr(id)"
                                class="ml-0.5 rounded-full hover:bg-sky-200 p-0.5 leading-none transition">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </div>

                    <!-- Picker row -->
                    <div v-if="brs.length === 0" class="text-sm text-slate-400 py-4 text-center border border-dashed border-slate-200 rounded-lg">
                        No business requirements in this project yet.
                    </div>
                    <div v-else class="flex items-end gap-2">
                        <!-- Search -->
                        <div class="flex-1">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Search</label>
                            <input v-model="search" type="text" placeholder="Filter by title or ref…"
                                class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                        </div>
                        <!-- Dropdown -->
                        <div class="flex-1">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Select BR</label>
                            <select v-model="selectVal"
                                class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                                <option v-if="filteredBrs.length === 0" value="" disabled>
                                    {{ brs.length > form.br_ids.length ? 'No match' : 'All added' }}
                                </option>
                                <option v-else value="">— Select —</option>
                                <option v-for="br in filteredBrs" :key="br.id" :value="br.id">
                                    {{ br.ref }} — {{ br.title }}
                                </option>
                            </select>
                        </div>
                        <!-- Add button -->
                        <button type="button" :disabled="!selectVal" @click="addBr"
                            class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-40 flex-shrink-0">
                            Add
                        </button>
                    </div>
                    <p v-if="form.errors.br_ids" class="text-xs text-red-500 mt-1">{{ form.errors.br_ids }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                        Create Suite
                    </button>
                    <a :href="route('projects.test-suites.index', { project: project.id })"
                        class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
