<script setup lang="ts">
import RolePermissionsEditor from '@/components/RolePermissionsEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

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
</script>

<template>
    <Head :title="`${role.name} — Permissions`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <RolePermissionsEditor
                :role="role"
                :groups="groups"
                :assigned="assigned"
                :submit-route="route('admin.roles.permissions.update', { role: role.id })"
                :readonly="role.is_admin"
                readonly-message="Admin is a superuser — all permissions are always granted and cannot be modified."
            />
        </div>
    </AppLayout>
</template>
