<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

type TierOption = { id: number; tier: string; seats: number; label: string; sort_order: number };
type UserOption = { id: number; name: string; email: string };

defineProps<{
    tierOptions: TierOption[];
    users: UserOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Organisations', href: route('admin.organizations.index') },
    { title: 'New Organisation', href: '#' },
];

const form = useForm({
    name:           '',
    slug:           '',
    owner_id:       null as number | null,
    tier_option_id: null as number | null,
    is_active:      true,
});

function autoSlug() {
    if (!form.slug) {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}

function submit() {
    form.post(route('admin.organizations.store'));
}

const tierColor: Record<string, string> = {
    basic:    'text-slate-600',
    starter:  'text-sky-700',
    standard: 'text-violet-700',
    pro:      'text-amber-700',
};
</script>

<template>
    <Head title="New Organisation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">New Organisation</h1>
                <p class="text-sm text-slate-500 mt-0.5">Create a new company and assign an initial subscription plan.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 bg-white border border-slate-200 rounded-xl p-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" @blur="autoSlug" type="text" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        placeholder="Acme Corp" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Slug <span class="text-red-500">*</span></label>
                    <input v-model="form.slug" type="text" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/40"
                        placeholder="acme-corp" />
                    <p class="text-xs text-slate-400 mt-1">Lowercase letters, numbers, hyphens only.</p>
                    <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
                </div>

                <!-- Owner -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Owner</label>
                    <select v-model="form.owner_id"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option :value="null">— No owner —</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </select>
                </div>

                <!-- Plan -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Subscription Plan <span class="text-red-500">*</span></label>
                    <div class="space-y-1.5">
                        <label v-for="opt in tierOptions" :key="opt.id"
                            class="flex items-center gap-3 p-2.5 border rounded-lg cursor-pointer transition-colors"
                            :class="form.tier_option_id === opt.id
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" :value="opt.id" v-model="form.tier_option_id" class="accent-primary" />
                            <span class="text-sm font-medium" :class="tierColor[opt.tier]">{{ opt.label }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.tier_option_id" class="text-red-500 text-xs mt-1">{{ form.errors.tier_option_id }}</p>
                </div>

                <!-- Active -->
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.is_active" class="accent-primary" />
                    <span class="text-sm text-slate-700">Active</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50">
                        Create Organisation
                    </button>
                    <a :href="route('admin.organizations.index')"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
