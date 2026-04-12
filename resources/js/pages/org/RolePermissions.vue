<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

type PermissionItem = { id: number; key: string; label: string };
type PermissionGroup = { group: string; permissions: PermissionItem[] };
type RoleInfo = { id: number; name: string; is_system: boolean; is_org_scoped: boolean };

const props = defineProps<{
    role: RoleInfo;
    groups: PermissionGroup[];
    assigned: number[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organisation', href: route('org.users.index') },
    { title: 'Roles', href: route('org.roles.index') },
    { title: props.role.name, href: '#' },
];

const form = useForm({ permissions: [...props.assigned] });

function toggle(id: number) {
    const idx = form.permissions.indexOf(id);
    if (idx === -1) form.permissions.push(id);
    else form.permissions.splice(idx, 1);
}

function isChecked(id: number) {
    return form.permissions.includes(id);
}

function submit() {
    form.put(route('org.roles.permissions.update', props.role.id));
}
</script>

<template>
    <Head :title="`${role.name} — Permissions`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ role.name }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Configure which permissions this role grants.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div v-for="group in groups" :key="group.group"
                    class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ group.group }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-slate-100">
                        <label v-for="perm in group.permissions" :key="perm.id"
                            class="flex items-center gap-3 px-4 py-3 bg-white hover:bg-slate-50 cursor-pointer transition-colors">
                            <input type="checkbox" :checked="isChecked(perm.id)"
                                @change="toggle(perm.id)"
                                class="accent-primary w-4 h-4 rounded" />
                            <div>
                                <p class="text-sm text-slate-800">{{ perm.label }}</p>
                                <p class="text-[10px] font-mono text-slate-400">{{ perm.key }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        Save Permissions
                    </button>
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Saved.</p>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
