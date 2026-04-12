<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

type PermissionItem = { id: number; key: string; label: string };
type PermissionGroup = { group: string; permissions: PermissionItem[] };

const props = defineProps<{
    role: { id: number; name: string; is_system: boolean; is_admin: boolean };
    groups: PermissionGroup[];
    assigned: number[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Roles', href: route('admin.roles.index') },
    { title: props.role.name, href: '#' },
];

const form = useForm({
    permission_ids: [...props.assigned],
});

function toggle(id: number) {
    const idx = form.permission_ids.indexOf(id);
    if (idx === -1) {
        form.permission_ids.push(id);
    } else {
        form.permission_ids.splice(idx, 1);
    }
}

function toggleGroup(group: PermissionGroup) {
    const ids = group.permissions.map(p => p.id);
    const allOn = ids.every(id => form.permission_ids.includes(id));
    if (allOn) {
        form.permission_ids = form.permission_ids.filter(id => !ids.includes(id));
    } else {
        ids.forEach(id => { if (!form.permission_ids.includes(id)) form.permission_ids.push(id); });
    }
}

function groupAllChecked(group: PermissionGroup) {
    return group.permissions.every(p => form.permission_ids.includes(p.id));
}

function groupPartiallyChecked(group: PermissionGroup) {
    return group.permissions.some(p => form.permission_ids.includes(p.id)) && !groupAllChecked(group);
}

function save() {
    form.put(route('admin.roles.permissions.update', props.role.id));
}
</script>

<template>
    <Head :title="`${role.name} — Permissions`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ role.name }}</h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        <span v-if="role.is_admin">Admin is a superuser — all permissions are always granted and cannot be modified.</span>
                        <span v-else>Configure what this role can view and do across the application.</span>
                    </p>
                </div>
                <button v-if="!role.is_admin" @click="save" :disabled="form.processing"
                    class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 disabled:opacity-50 transition">
                    Save Changes
                </button>
            </div>

            <div v-if="role.is_admin" class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                Admin bypasses all permission checks. This matrix is read-only.
            </div>

            <div class="space-y-4">
                <div v-for="group in groups" :key="group.group"
                    class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <!-- Group header -->
                    <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <input type="checkbox"
                            :checked="role.is_admin || groupAllChecked(group)"
                            :indeterminate="!role.is_admin && groupPartiallyChecked(group)"
                            :disabled="role.is_admin"
                            @change="toggleGroup(group)"
                            class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer disabled:cursor-default" />
                        <span class="text-sm font-semibold text-slate-700">{{ group.group }}</span>
                    </div>
                    <!-- Permissions grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0 divide-y divide-slate-100 sm:divide-y-0">
                        <label v-for="perm in group.permissions" :key="perm.id"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 cursor-pointer"
                            :class="{ 'cursor-default': role.is_admin }">
                            <input type="checkbox"
                                :checked="role.is_admin || form.permission_ids.includes(perm.id)"
                                :disabled="role.is_admin"
                                @change="toggle(perm.id)"
                                class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer disabled:cursor-default" />
                            <span class="text-sm text-slate-700">{{ perm.label }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div v-if="!role.is_admin" class="flex justify-end">
                <button @click="save" :disabled="form.processing"
                    class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 disabled:opacity-50 transition">
                    Save Changes
                </button>
            </div>
        </div>
    </AppLayout>
</template>
