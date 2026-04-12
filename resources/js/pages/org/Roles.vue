<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type RoleRow = {
    id: number; name: string; slug: string;
    is_system: boolean; is_org_scoped: boolean;
    user_count: number; permission_count: number;
};

defineProps<{ roles: RoleRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organisation', href: route('org.users.index') },
    { title: 'Roles', href: route('org.roles.index') },
];

const newForm = useForm({ name: '' });
function createRole() {
    newForm.post(route('org.roles.store'), { onSuccess: () => newForm.reset() });
}

const editingId = ref<number | null>(null);
const editForm = useForm({ name: '' });
function startEdit(r: RoleRow) {
    editingId.value = r.id;
    editForm.name = r.name;
}
function saveEdit(r: RoleRow) {
    editForm.patch(route('org.roles.update', r.id), {
        onSuccess: () => { editingId.value = null; },
    });
}

function deleteRole(r: RoleRow) {
    if (!confirm(`Delete role "${r.name}"?`)) return;
    useForm({}).delete(route('org.roles.destroy', r.id));
}
</script>

<template>
    <Head title="Organisation Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Roles & Permissions</h1>
                <p class="text-sm text-slate-500 mt-0.5">System roles are read-only. Create custom roles for your organisation.</p>
            </div>

            <!-- Create custom role -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">New Custom Role</h2>
                <form @submit.prevent="createRole" class="flex gap-3 items-end">
                    <div class="flex-1 max-w-xs">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Role name</label>
                        <input v-model="newForm.name" type="text" required placeholder="e.g. QA Lead"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                        <p v-if="newForm.errors.name" class="text-red-500 text-xs mt-1">{{ newForm.errors.name }}</p>
                    </div>
                    <button type="submit" :disabled="newForm.processing"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        Create
                    </button>
                </form>
            </div>

            <!-- Roles table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Permissions</th>
                            <th class="px-4 py-3 text-left">Users</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="r in roles" :key="r.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <template v-if="editingId === r.id">
                                    <div class="flex items-center gap-2">
                                        <input v-model="editForm.name" type="text"
                                            class="border border-slate-200 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                        <button @click="saveEdit(r)" class="text-xs font-medium text-emerald-600 hover:underline">Save</button>
                                        <button @click="editingId = null" class="text-xs text-slate-400 hover:underline">Cancel</button>
                                    </div>
                                </template>
                                <span v-else class="font-medium text-slate-800">{{ r.name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="r.is_system
                                        ? 'bg-slate-100 text-slate-500'
                                        : 'bg-violet-100 text-violet-700'">
                                    {{ r.is_system ? 'System' : 'Custom' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ r.permission_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ r.user_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <Link v-if="r.is_org_scoped"
                                        :href="route('org.roles.permissions.edit', r.id)"
                                        class="text-xs font-medium text-primary hover:underline">
                                        Permissions
                                    </Link>
                                    <button v-if="r.is_org_scoped && editingId !== r.id"
                                        @click="startEdit(r)"
                                        class="text-xs font-medium text-slate-600 hover:underline">
                                        Rename
                                    </button>
                                    <button v-if="r.is_org_scoped"
                                        @click="deleteRole(r)"
                                        class="text-xs font-medium text-red-500 hover:underline">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
