<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SubscriptionTier } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type OrgInfo     = { id: number; name: string; seats_used: number };
type SubInfo     = { id: number; status: string; tier: SubscriptionTier; tier_label: string; seats: number; starts_at: string; ends_at: string | null; trial_ends_at: string | null };
type TierOption  = { id: number; tier: SubscriptionTier; seats: number; label: string; sort_order: number };
type FeatureMatrix = Record<string, string[]>;

const props = defineProps<{
    organization: OrgInfo;
    subscription: SubInfo | null;
    tierOptions: TierOption[];
    featureMatrix: FeatureMatrix;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: route('profile.edit') },
    { title: 'Subscription', href: route('subscription.show') },
];

const form = useForm({
    tier_option_id: props.subscription?.id ?? null as number | null,
});

function submit() {
    form.patch(route('subscription.update'));
}

const tiers: SubscriptionTier[] = ['basic', 'starter', 'standard', 'pro'];

const tierMeta: Record<SubscriptionTier, { label: string; color: string; ring: string; bg: string }> = {
    basic:    { label: 'Basic',    color: 'text-slate-700',   ring: 'ring-slate-300',  bg: 'bg-slate-50' },
    starter:  { label: 'Starter',  color: 'text-sky-700',     ring: 'ring-sky-400',    bg: 'bg-sky-50' },
    standard: { label: 'Standard', color: 'text-violet-700',  ring: 'ring-violet-400', bg: 'bg-violet-50' },
    pro:      { label: 'Pro',      color: 'text-amber-700',   ring: 'ring-amber-400',  bg: 'bg-amber-50' },
};

const featureRows = [
    { key: 'projects',       label: 'Projects & Tasks' },
    { key: 'br',             label: 'Business Requirements' },
    { key: 'tr',             label: 'Technical Requirements' },
    { key: 'tc',             label: 'Test Cases' },
    { key: 'test_runs',      label: 'Test Runs & Suites' },
    { key: 'sprints',        label: 'Sprints' },
    { key: 'rtm',            label: 'RTM View & Export' },
    { key: 'reports',        label: 'Reports & Analytics' },
    { key: 'imports_exports', label: 'CSV Import / Export' },
];

function tierHasFeature(tier: SubscriptionTier, featureKey: string): boolean {
    return (props.featureMatrix[tier] ?? []).includes(featureKey);
}

const optionsByTier = computed(() => {
    const map: Record<string, TierOption[]> = {};
    for (const opt of props.tierOptions) {
        if (!map[opt.tier]) map[opt.tier] = [];
        map[opt.tier].push(opt);
    }
    return map;
});

const selectedTier = ref<SubscriptionTier | null>(props.subscription?.tier ?? null);

function selectOption(opt: TierOption) {
    form.tier_option_id = opt.id;
    selectedTier.value = opt.tier;
}

const seatPct = computed(() => {
    if (!props.subscription) return 0;
    return Math.min(100, Math.round(props.organization.seats_used / props.subscription.seats * 100));
});

const statusColor: Record<string, string> = {
    active:    'bg-emerald-100 text-emerald-700',
    trial:     'bg-blue-100 text-blue-700',
    expired:   'bg-red-100 text-red-600',
    cancelled: 'bg-slate-100 text-slate-500',
};
</script>

<template>
    <Head title="Subscription" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl space-y-8">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Subscription</h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage the plan for <strong>{{ organization.name }}</strong>.</p>
            </div>

            <!-- Current plan summary -->
            <div v-if="subscription" class="bg-white border border-slate-200 rounded-xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700">Current Plan</h2>
                <div class="flex items-center gap-3">
                    <span class="text-base font-bold text-slate-900">{{ subscription.tier_label }}</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                        :class="statusColor[subscription.status]">
                        {{ subscription.status }}
                    </span>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Seats used</span>
                        <span>{{ organization.seats_used }} / {{ subscription.seats }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                            :class="seatPct >= 90 ? 'bg-red-500' : seatPct >= 70 ? 'bg-amber-400' : 'bg-emerald-500'"
                            :style="{ width: seatPct + '%' }">
                        </div>
                    </div>
                </div>
                <div v-if="subscription.trial_ends_at" class="text-sm text-amber-600">
                    Trial ends: <strong>{{ subscription.trial_ends_at }}</strong>
                </div>
            </div>

            <!-- Feature comparison table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Feature Comparison</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 w-48">Feature</th>
                                <th v-for="tier in tiers" :key="tier"
                                    class="px-4 py-3 text-center text-xs font-semibold"
                                    :class="[tierMeta[tier].color, subscription?.tier === tier ? tierMeta[tier].bg : '']">
                                    {{ tierMeta[tier].label }}
                                    <span v-if="subscription?.tier === tier"
                                        class="block text-[10px] font-normal opacity-70">Current</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="row in featureRows" :key="row.key" class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-slate-700">{{ row.label }}</td>
                                <td v-for="tier in tiers" :key="tier" class="px-4 py-2.5 text-center"
                                    :class="subscription?.tier === tier ? tierMeta[tier].bg : ''">
                                    <span v-if="tierHasFeature(tier, row.key)" class="text-emerald-500 text-base">✓</span>
                                    <span v-else class="text-slate-300 text-base">✕</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Change plan -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
                <h2 class="text-sm font-semibold text-slate-700">Change Plan</h2>

                <div v-for="tier in tiers" :key="tier" class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide" :class="tierMeta[tier].color">
                        {{ tierMeta[tier].label }}
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <label v-for="opt in (optionsByTier[tier] ?? [])" :key="opt.id"
                            class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors text-sm"
                            :class="form.tier_option_id === opt.id
                                ? `${tierMeta[tier].ring} ring-2 ${tierMeta[tier].bg}`
                                : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" :value="opt.id" v-model="form.tier_option_id"
                                @change="selectOption(opt)" class="accent-primary" />
                            <span>{{ opt.seats }} seats</span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-4">
                    <button @click="submit" :disabled="form.processing || !form.tier_option_id"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-40">
                        Update Plan
                    </button>
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Plan updated.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
