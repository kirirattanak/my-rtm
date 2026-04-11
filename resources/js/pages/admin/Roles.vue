<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    roles: { id: number; name: string; slug: string; is_system: boolean; user_count: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Roles', href: route('admin.roles.index') },
];

const showCreate = ref(false);
const createForm = useForm({ name: '' });

function createRole() {
    createForm.post(route('admin.roles.store'), {
        onSuccess: () => { createForm.reset(); showCreate.value = false; },
    });
}

const editingId = ref<number | null>(null);
const editForm = useForm({ name: '' });

function startRename(role: { id: number; name: string }) {
    editingId.value = role.id;
    editForm.name = role.name;
}

function saveRename(id: number) {
    editForm.patch(route('admin.roles.update', id), {
        onSuccess: () => { editingId.value = null; },
    });
}

function deleteRole(id: number) {
    if (confirm('Delete this role?')) {
        router.delete(route('admin.roles.destroy', id));
    }
}
</script>

<template>
    <Head title="Roles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Roles</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage roles and configure their permissions.</p>
                </div>
                <button @click="showCreate = true"
                    class="inline-flex items-center gap-2 bg-primary text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary/90 transition">
                    + New Role
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showCreate" class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <input v-model="createForm.name" type="text" placeholder="Role name"
                    class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                    @keydown.enter="createRole" @keydown.escape="showCreate = false; createForm.reset()" />
                <button @click="createRole" :disabled="createForm.processing"
                    class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 disabled:opacity-50 transition">
                    Create
                </button>
                <button @click="showCreate = false; createForm.reset()"
                    class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                <p v-if="createForm.errors.name" class="text-xs text-red-500">{{ createForm.errors.name }}</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Role</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Users</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <div v-if="editingId === role.id" class="flex items-center gap-2">
                                    <input v-model="editForm.name" type="text"
                                        class="border border-slate-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        @keydown.enter="saveRename(role.id)" @keydown.escape="editingId = null" />
                                    <button @click="saveRename(role.id)" class="text-xs text-emerald-600 font-medium hover:underline">Save</button>
                                    <button @click="editingId = null" class="text-xs text-slate-400 hover:text-slate-600">Cancel</button>
                                </div>
                                <span v-else class="font-medium text-slate-800">{{ role.name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="role.is_system ? 'bg-slate-100 text-slate-500' : 'bg-violet-100 text-violet-700'">
                                    {{ role.is_system ? 'System' : 'Custom' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ role.user_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <Link :href="route('admin.roles.permissions.edit', role.id)"
                                        class="text-xs text-primary font-medium hover:underline">
                                        Permissions
                                    </Link>
                                    <template v-if="!role.is_system">
                                        <button @click="startRename(role)" class="text-xs text-slate-500 hover:text-slate-700">Rename</button>
                                        <button @click="deleteRole(role.id)"
                                            :disabled="role.user_count > 0"
                                            class="text-xs text-red-500 hover:text-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                            Delete
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
