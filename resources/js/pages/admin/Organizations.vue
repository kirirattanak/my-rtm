<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

type OrgRow = {
    id: number; name: string; slug: string; is_active: boolean;
    owner: { id: number; name: string } | null;
    tier: string | null; tier_label: string | null; status: string | null;
    seats: number | null; seats_used: number; users_count: number;
    created_at: string;
};

defineProps<{ organizations: OrgRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Organisations', href: route('admin.organizations.index') },
];

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
    <Head title="Organisations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Organisations</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage companies and their subscription plans.</p>
                </div>
                <Link :href="route('admin.organizations.create')"
                    class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity">
                    New Organisation
                </Link>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Organisation</th>
                            <th class="px-4 py-3 text-left">Owner</th>
                            <th class="px-4 py-3 text-left">Plan</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Seats</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="org in organizations" :key="org.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ org.name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ org.slug }}</div>
                                <span v-if="!org.is_active"
                                    class="inline-block mt-0.5 text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-medium">
                                    Inactive
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ org.owner?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="org.tier"
                                    class="inline-block text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="tierColor[org.tier] ?? 'bg-slate-100 text-slate-600'">
                                    {{ org.tier_label }}
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="org.status"
                                    class="inline-block text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                                    :class="statusColor[org.status] ?? 'bg-slate-100 text-slate-500'">
                                    {{ org.status }}
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="org.seats !== null" class="text-slate-700">
                                    {{ org.seats_used }}
                                    <span class="text-slate-400">/ {{ org.seats }}</span>
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ org.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.organizations.show', org.id)"
                                    class="text-xs font-medium text-primary hover:underline">
                                    View
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="organizations.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400 italic">
                                No organisations yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
