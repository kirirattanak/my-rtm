<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

type InvitationRow = {
    id: number; email: string; role: string; organization: string | null;
    invited_by: string; status: string; expires_at: string; created_at: string;
};
type RoleOption = { id: number; name: string; slug: string; is_system: boolean };
type OrgOption  = { id: number; name: string };

const props = defineProps<{
    invitations: InvitationRow[];
    roles: RoleOption[];
    organizations: OrgOption[];
    is_admin: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Invitations', href: '/admin/invitations' },
];

const form = useForm({
    email:           '',
    role:            'viewer',
    organization_id: null as number | null,
});

function send() {
    form.post(route('admin.invitations.store'), {
        onSuccess: () => form.reset(),
    });
}

function revoke(invitation: InvitationRow) {
    router.delete(route('admin.invitations.destroy', invitation.id));
}

const statusClass: Record<string, string> = {
    pending:  'bg-amber-100 text-amber-700',
    accepted: 'bg-emerald-100 text-emerald-700',
    expired:  'bg-slate-100 text-slate-500',
};
</script>

<template>
    <Head title="Invitations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Invitations</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Invite people to join your workspace.</p>
                </div>
                <a :href="route('admin.users.index')"
                   class="text-sm text-slate-500 hover:text-slate-700 font-medium">
                    ← Back to Users
                </a>
            </div>

            <!-- Send invitation form -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Send Invitation</h2>
                <form @submit.prevent="send" class="flex items-end gap-3 flex-wrap">
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Email address</label>
                        <input v-model="form.email" type="email" required placeholder="colleague@example.com"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.email }" />
                        <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div class="w-44">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Role</label>
                        <select v-model="form.role"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option v-for="r in roles" :key="r.slug" :value="r.slug">{{ r.name }}</option>
                        </select>
                    </div>
                    <!-- Organisation picker — admins only -->
                    <div v-if="is_admin" class="w-52">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Organisation</label>
                        <select v-model="form.organization_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.organization_id }">
                            <option :value="null">— none —</option>
                            <option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                        <p v-if="form.errors.organization_id" class="text-xs text-red-500 mt-1">{{ form.errors.organization_id }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                        Send Invite
                    </button>
                </form>
            </div>

            <!-- Invitations table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500">Email</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-36">Role</th>
                            <th v-if="is_admin" class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-40">Organisation</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-32">Invited by</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-24">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-28">Expires</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="invitations.length === 0">
                            <td :colspan="is_admin ? 7 : 6" class="px-4 py-8 text-center text-sm text-slate-400">No invitations yet.</td>
                        </tr>
                        <tr v-for="inv in invitations" :key="inv.id"
                            class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-700">{{ inv.email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ roles.find(r => r.slug === inv.role)?.name ?? inv.role }}</td>
                            <td v-if="is_admin" class="px-4 py-3 text-slate-500 text-xs">{{ inv.organization ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ inv.invited_by }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                    :class="statusClass[inv.status]">
                                    {{ inv.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ inv.expires_at }}</td>
                            <td class="px-4 py-3">
                                <button v-if="inv.status === 'pending'"
                                    @click="revoke(inv)"
                                    class="text-xs text-red-500 font-medium hover:underline">
                                    Revoke
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
