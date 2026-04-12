<script setup lang="ts">
import RolePermissionsEditor from '@/components/RolePermissionsEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

type PermissionItem = { id: number; key: string; label: string };
type PermissionGroup = { group: string; permissions: PermissionItem[] };

const props = defineProps<{
    role: { id: number; name: string; is_system: boolean; is_org_scoped: boolean };
    groups: PermissionGroup[];
    assigned: number[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organisation', href: route('org.users.index') },
    { title: 'Roles', href: route('org.roles.index') },
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
                :submit-route="route('org.roles.permissions.update', { role: role.id })"
                :readonly="role.is_system"
                readonly-message="System roles are managed by the administrator. This matrix is read-only."
            />
        </div>
    </AppLayout>
</template>
