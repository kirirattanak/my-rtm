<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type UserRow = { id: number; name: string; email: string; role_id: number | null; role_name: string | null; is_active: boolean; created_at: string };
type RoleOption = { id: number; name: string; is_system: boolean };

defineProps<{
    users: UserRow[];
    roles: RoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Users', href: '/admin/users' },
];

const editingRole = ref<number | null>(null);
const roleForm = useForm({ role_id: null as number | null });

function startEditRole(user: UserRow) {
    editingRole.value = user.id;
    roleForm.role_id = user.role_id;
}

function saveRole(user: UserRow) {
    roleForm.patch(route('admin.users.update-role', user.id), {
        onSuccess: () => { editingRole.value = null; },
    });
}

function toggleActive(user: UserRow) {
    router.patch(route('admin.users.toggle-active', user.id));
}
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">User Management</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage user roles and account status.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('admin.roles.index')"
                       class="inline-flex items-center gap-2 border border-slate-200 text-slate-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                        Roles
                    </a>
                    <a :href="route('admin.invitations.index')"
                       class="inline-flex items-center gap-2 bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        Invitations
                    </a>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500">Name</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500">Email</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-48">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-24">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-24">Joined</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id"
                            class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ user.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <div v-if="editingRole === user.id" class="flex items-center gap-2">
                                    <select v-model="roleForm.role_id"
                                        class="border border-slate-200 rounded-lg px-2 py-1 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                    </select>
                                    <button @click="saveRole(user)"
                                        class="text-xs text-emerald-600 font-medium hover:underline">Save</button>
                                    <button @click="editingRole = null"
                                        class="text-xs text-slate-400 hover:underline">Cancel</button>
                                </div>
                                <span v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 cursor-pointer"
                                    @click="startEditRole(user)">
                                    {{ user.role_name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ user.created_at }}</td>
                            <td class="px-4 py-3">
                                <button @click="toggleActive(user)"
                                    class="text-xs font-medium hover:underline"
                                    :class="user.is_active ? 'text-red-500' : 'text-emerald-600'">
                                    {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
