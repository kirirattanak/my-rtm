<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Member = { id: number; name: string; email: string; is_active: boolean; role_id: number | null; role_name: string | null; is_owner: boolean };
type RoleOption = { id: number; name: string; slug: string; is_system: boolean; organization_id: number | null };
type PendingInvite = { id: number; email: string; expires_at: string };

const props = defineProps<{
    members: Member[];
    roles: RoleOption[];
    seats: number | null;
    seats_used: number;
    pending_invitations: PendingInvite[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organisation', href: route('org.users.index') },
    { title: 'Users', href: route('org.users.index') },
];

const inviteForm = useForm({ email: '', role_id: null as number | null });

function sendInvite() {
    inviteForm.post(route('org.invitations.store'), { onSuccess: () => inviteForm.reset() });
}

function revokeInvite(id: number) {
    useForm({}).delete(route('org.invitations.destroy', id));
}

const editingRole = ref<number | null>(null);
const roleForm = useForm({ role_id: null as number | null });

function startEditRole(m: Member) {
    editingRole.value = m.id;
    roleForm.role_id = m.role_id;
}

function saveRole(m: Member) {
    roleForm.patch(route('org.users.update-role', m.id), {
        onSuccess: () => { editingRole.value = null; },
    });
}

function toggleActive(m: Member) {
    useForm({}).patch(route('org.users.toggle-active', m.id));
}

const seatPct = props.seats ? Math.min(100, Math.round(props.seats_used / props.seats * 100)) : 0;
</script>

<template>
    <Head title="Organisation Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Organisation Users</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage members and invite new users.</p>
                </div>
                <div v-if="seats !== null" class="text-right">
                    <p class="text-sm font-medium text-slate-700">{{ seats_used }} / {{ seats }} seats</p>
                    <div class="w-32 bg-slate-100 rounded-full h-1.5 mt-1">
                        <div class="h-1.5 rounded-full transition-all"
                            :class="seatPct >= 90 ? 'bg-red-500' : seatPct >= 70 ? 'bg-amber-400' : 'bg-emerald-500'"
                            :style="{ width: seatPct + '%' }">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invite form -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700">Invite New User</h2>
                <form @submit.prevent="sendInvite" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                        <input v-model="inviteForm.email" type="email" required placeholder="user@example.com"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                        <p v-if="inviteForm.errors.email" class="text-red-500 text-xs mt-1">{{ inviteForm.errors.email }}</p>
                    </div>
                    <div class="w-48">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Role</label>
                        <select v-model="inviteForm.role_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option :value="null" disabled>— Select role —</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="inviteForm.processing"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        Send Invite
                    </button>
                </form>

                <!-- Pending invitations -->
                <div v-if="pending_invitations.length" class="pt-2 border-t border-slate-100">
                    <p class="text-xs font-medium text-slate-500 mb-2">Pending invitations</p>
                    <div class="space-y-1.5">
                        <div v-for="inv in pending_invitations" :key="inv.id"
                            class="flex items-center justify-between text-sm bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            <span class="text-slate-700">{{ inv.email }}</span>
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span>Expires {{ inv.expires_at }}</span>
                                <button @click="revokeInvite(inv.id)"
                                    class="text-red-500 hover:underline font-medium">Revoke</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Members table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in members" :key="m.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-900">{{ m.name }}</span>
                                <span v-if="m.is_owner"
                                    class="ml-2 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">
                                    Owner
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ m.email }}</td>
                            <td class="px-4 py-3">
                                <template v-if="editingRole === m.id && !m.is_owner">
                                    <div class="flex items-center gap-2">
                                        <select v-model="roleForm.role_id"
                                            class="border border-slate-200 rounded-lg px-2 py-1 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                        </select>
                                        <button @click="saveRole(m)"
                                            class="text-xs font-medium text-emerald-600 hover:underline">Save</button>
                                        <button @click="editingRole = null"
                                            class="text-xs text-slate-400 hover:underline">Cancel</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="text-slate-700">{{ m.role_name ?? '—' }}</span>
                                    <button v-if="!m.is_owner" @click="startEditRole(m)"
                                        class="ml-2 text-xs text-primary hover:underline">Edit</button>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="m.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ m.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="!m.is_owner" @click="toggleActive(m)"
                                    class="text-xs font-medium"
                                    :class="m.is_active ? 'text-red-500 hover:underline' : 'text-emerald-600 hover:underline'">
                                    {{ m.is_active ? 'Deactivate' : 'Reactivate' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="members.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 italic">No members yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
