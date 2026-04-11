<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type MemberRow = { id: number; user_id: number; name: string; email: string; role_id: number | null; role_name: string | null };
type RoleOption = { id: number; name: string };

const props = defineProps<{
    project: { id: number; name: string };
    members: MemberRow[];
    addable_users: { id: number; name: string; email: string }[];
    roles: RoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Members', href: route('projects.members.index', props.project.id) },
];

const addForm = useForm({ user_id: '' as string | number, role_id: props.roles[0]?.id ?? null as number | null });

function addMember() {
    addForm.post(route('projects.members.store', props.project.id), {
        onSuccess: () => addForm.reset(),
    });
}

const editingId = ref<number | null>(null);
const editRoleId = ref<number | null>(null);

function startEdit(member: MemberRow) {
    editingId.value = member.id;
    editRoleId.value = member.role_id;
}

function saveRole(member: MemberRow) {
    router.patch(route('projects.members.update', { project: props.project.id, member: member.id }), {
        role_id: editRoleId.value,
    }, { onSuccess: () => { editingId.value = null; } });
}

function removeMember(member: MemberRow) {
    if (confirm(`Remove ${member.name} from this project?`)) {
        router.delete(route('projects.members.destroy', { project: props.project.id, member: member.id }));
    }
}
</script>

<template>
    <Head :title="`Members · ${project.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-3xl stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Team Members</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
                </div>
                <a :href="route('projects.show', project.id)"
                    class="text-sm text-slate-500 hover:text-slate-700 font-medium">← Back to Project</a>
            </div>

            <!-- Add member -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Add Member</h2>
                <form @submit.prevent="addMember" class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-slate-600 mb-1">User</label>
                        <select v-model="addForm.user_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': addForm.errors.user_id }">
                            <option value="" disabled>Select a user...</option>
                            <option v-for="u in addable_users" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <p v-if="addForm.errors.user_id" class="text-xs text-red-500 mt-1">{{ addForm.errors.user_id }}</p>
                    </div>
                    <div class="w-48">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Role</label>
                        <select v-model="addForm.role_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="addForm.processing || !addForm.user_id"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                        Add
                    </button>
                </form>
                <p v-if="addable_users.length === 0" class="text-xs text-slate-400 mt-3">
                    All active users are already members of this project.
                </p>
            </div>

            <!-- Members list -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500">Member</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-52">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="members.length === 0">
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-400">No members yet.</td>
                        </tr>
                        <tr v-for="member in members" :key="member.id"
                            class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                        {{ member.name.split(' ').map((w: string) => w[0]).slice(0,2).join('').toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ member.name }}</p>
                                        <p class="text-xs text-slate-400">{{ member.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="editingId === member.id" class="flex items-center gap-2">
                                    <select v-model="editRoleId"
                                        class="border border-slate-200 rounded-lg px-2 py-1 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                    </select>
                                    <button @click="saveRole(member)" class="text-xs text-emerald-600 font-medium hover:underline">Save</button>
                                    <button @click="editingId = null" class="text-xs text-slate-400 hover:underline">Cancel</button>
                                </div>
                                <span v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 cursor-pointer"
                                    @click="startEdit(member)">
                                    {{ member.role_name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button @click="removeMember(member)"
                                    class="text-xs text-red-500 font-medium hover:underline">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
