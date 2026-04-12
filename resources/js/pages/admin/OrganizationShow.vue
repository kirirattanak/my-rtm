<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type OrgDetail = { id: number; name: string; slug: string; is_active: boolean; owner: { id: number; name: string; email: string } | null; created_at: string };
type SubDetail  = { id: number; status: string; tier: string; tier_label: string; seats: number; seats_used: number; starts_at: string; ends_at: string | null; trial_ends_at: string | null };
type Member     = { id: number; name: string; email: string; is_active: boolean; role: string | null };
type TierOption = { id: number; tier: string; seats: number; label: string; sort_order: number };

const props = defineProps<{
    organization: OrgDetail;
    subscription: SubDetail | null;
    members: Member[];
    tierOptions: TierOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Organisations', href: route('admin.organizations.index') },
    { title: props.organization.name, href: '#' },
];

const editingDetails = ref(false);
const detailsForm = useForm({
    name:      props.organization.name,
    owner_id:  props.organization.owner?.id ?? null as number | null,
    is_active: props.organization.is_active,
});

function saveDetails() {
    detailsForm.patch(route('admin.organizations.update', props.organization.id), {
        onSuccess: () => { editingDetails.value = false; },
    });
}

const editingSub = ref(false);
const subForm = useForm({
    tier_option_id: props.subscription?.id ?? null as number | null,
    status:         props.subscription?.status ?? 'active',
});

function saveSub() {
    subForm.patch(route('admin.organizations.subscription.update', props.organization.id), {
        onSuccess: () => { editingSub.value = false; },
    });
}

const tierColor: Record<string, string> = {
    basic:    'bg-slate-100 text-slate-600',
    starter:  'bg-sky-100 text-sky-700',
    standard: 'bg-violet-100 text-violet-700',
    pro:      'bg-amber-100 text-amber-700',
};

const statusColor: Record<string, string> = {
    active:    'bg-emerald-100 text-emerald-700',
    trial:     'bg-blue-100 text-blue-700',
    expired:   'bg-red-100 text-red-600',
    cancelled: 'bg-slate-100 text-slate-500',
};
</script>

<template>
    <Head :title="organization.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <h1 class="text-xl font-semibold text-slate-900">{{ organization.name }}</h1>

            <!-- Details card -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Organisation Details</h2>
                    <button @click="editingDetails = !editingDetails"
                        class="text-xs font-medium text-primary hover:underline">
                        {{ editingDetails ? 'Cancel' : 'Edit' }}
                    </button>
                </div>

                <form v-if="editingDetails" @submit.prevent="saveDetails" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Name</label>
                        <input v-model="detailsForm.name" type="text" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="detailsForm.is_active" class="accent-primary" />
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                    <button type="submit" :disabled="detailsForm.processing"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        Save
                    </button>
                </form>

                <dl v-else class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Name</dt>
                        <dd class="font-medium text-slate-800">{{ organization.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Slug</dt>
                        <dd class="font-mono text-slate-600">{{ organization.slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Owner</dt>
                        <dd>{{ organization.owner?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Status</dt>
                        <dd>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="organization.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'">
                                {{ organization.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Created</dt>
                        <dd>{{ organization.created_at }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Subscription card -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Subscription</h2>
                    <button @click="editingSub = !editingSub"
                        class="text-xs font-medium text-primary hover:underline">
                        {{ editingSub ? 'Cancel' : 'Change Plan' }}
                    </button>
                </div>

                <form v-if="editingSub" @submit.prevent="saveSub" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Plan</label>
                        <div class="space-y-1.5">
                            <label v-for="opt in tierOptions" :key="opt.id"
                                class="flex items-center gap-3 p-2.5 border rounded-lg cursor-pointer transition-colors"
                                :class="subForm.tier_option_id === opt.id ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" :value="opt.id" v-model="subForm.tier_option_id" class="accent-primary" />
                                <span class="text-sm">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                        <select v-model="subForm.status"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="trial">Trial</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="subForm.processing"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        Update Subscription
                    </button>
                </form>

                <div v-else-if="subscription" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="tierColor[subscription.tier]">
                            {{ subscription.tier_label }}
                        </span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full capitalize" :class="statusColor[subscription.status]">
                            {{ subscription.status }}
                        </span>
                    </div>
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold text-slate-800">{{ subscription.seats_used }}</span>
                        / {{ subscription.seats }} seats used
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-primary h-1.5 rounded-full transition-all"
                            :style="{ width: Math.min(100, subscription.seats_used / subscription.seats * 100) + '%' }">
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs text-slate-500 pt-1">
                        <div><dt>Started</dt><dd class="text-slate-700">{{ subscription.starts_at }}</dd></div>
                        <div v-if="subscription.trial_ends_at"><dt>Trial ends</dt><dd class="text-amber-600">{{ subscription.trial_ends_at }}</dd></div>
                        <div v-if="subscription.ends_at"><dt>Expires</dt><dd class="text-slate-700">{{ subscription.ends_at }}</dd></div>
                    </dl>
                </div>
                <p v-else class="text-sm text-slate-400 italic">No active subscription.</p>
            </div>

            <!-- Members -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Members ({{ members.length }})</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Role</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in members" :key="m.id" class="hover:bg-slate-50">
                            <td class="px-4 py-2 font-medium text-slate-800">{{ m.name }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ m.email }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ m.role ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs font-medium px-1.5 py-0.5 rounded-full"
                                    :class="m.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ m.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="members.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400 italic">No members.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
