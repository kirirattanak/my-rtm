<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type MemberRow = { id: number; user_id: number; name: string; email: string; role_id: number | null; role_name: string | null };
type RoleOption = { id: number; name: string };

interface CapacityRow {
    user_id: number;
    user_name: string;
    available_hours: number;
    focus_factor: number;
    effective_hours: number;
    notes: string | null;
    dirty?: boolean;
}

const props = defineProps<{
    project: { id: number; name: string };
    members: MemberRow[];
    addable_users: { id: number; name: string; email: string }[];
    roles: RoleOption[];
    can_manage_capacity: boolean;
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

// ── Capacity tab ──────────────────────────────────────────────────────────────
const activeTab = ref<'members' | 'capacity'>('members');

const now = new Date();
const capacityYear  = ref(now.getFullYear());
const capacityMonth = ref(now.getMonth() + 1);

const monthLabel = computed(() => {
    return new Date(capacityYear.value, capacityMonth.value - 1).toLocaleString('default', { month: 'long', year: 'numeric' });
});

function prevMonth() {
    if (capacityMonth.value === 1) { capacityMonth.value = 12; capacityYear.value--; }
    else { capacityMonth.value--; }
    loadCapacity();
}

function nextMonth() {
    if (capacityMonth.value === 12) { capacityMonth.value = 1; capacityYear.value++; }
    else { capacityMonth.value++; }
    loadCapacity();
}

const capacityRows = ref<CapacityRow[]>([]);
const capacityLoading = ref(false);
const capacitySaving = ref(false);

async function loadCapacity() {
    capacityLoading.value = true;
    const res = await fetch(
        route('projects.members.capacity.index', props.project.id)
        + `?year=${capacityYear.value}&month=${capacityMonth.value}`,
        { headers: { Accept: 'application/json' } }
    );
    const data: CapacityRow[] = await res.json();
    capacityLoading.value = false;

    // Merge with members list so every member appears
    const byUserId = new Map(data.map(r => [r.user_id, r]));
    capacityRows.value = props.members.map(m => {
        const existing = byUserId.get(m.user_id);
        return existing
            ? { ...existing, dirty: false }
            : { user_id: m.user_id, user_name: m.name, available_hours: 160, focus_factor: 0.80, effective_hours: 128, notes: null, dirty: false };
    });
}

function markDirty(row: CapacityRow) {
    row.dirty = true;
    row.effective_hours = parseFloat((row.available_hours * row.focus_factor).toFixed(2));
}

async function saveCapacity() {
    capacitySaving.value = true;
    const res = await fetch(route('projects.members.capacity.upsert', props.project.id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '', Accept: 'application/json' },
        body: JSON.stringify({
            year: capacityYear.value,
            month: capacityMonth.value,
            records: capacityRows.value.map(r => ({
                user_id: r.user_id,
                available_hours: r.available_hours,
                focus_factor: r.focus_factor,
                notes: r.notes,
            })),
        }),
    });
    capacitySaving.value = false;
    if (res.ok) {
        capacityRows.value.forEach(r => r.dirty = false);
    } else {
        alert(`Failed to save capacity (HTTP ${res.status}). Please try again.`);
    }
}

async function copyPreviousMonth() {
    const prevY = capacityMonth.value === 1 ? capacityYear.value - 1 : capacityYear.value;
    const prevM = capacityMonth.value === 1 ? 12 : capacityMonth.value - 1;
    const res = await fetch(
        route('projects.members.capacity.index', props.project.id) + `?year=${prevY}&month=${prevM}`,
        { headers: { Accept: 'application/json' } }
    );
    const data: CapacityRow[] = await res.json();
    const byUserId = new Map(data.map(r => [r.user_id, r]));
    capacityRows.value.forEach(row => {
        const prev = byUserId.get(row.user_id);
        if (prev) {
            row.available_hours = prev.available_hours;
            row.focus_factor = prev.focus_factor;
            row.notes = prev.notes;
            row.effective_hours = parseFloat((row.available_hours * row.focus_factor).toFixed(2));
            row.dirty = true;
        }
    });
}

// Load capacity on mount when tab is opened
function openCapacityTab() {
    activeTab.value = 'capacity';
    if (capacityRows.value.length === 0) loadCapacity();
}
</script>

<template>
    <Head :title="`Members · ${project.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-3xl stagger">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Team</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ project.name }}</p>
                </div>
                <a :href="route('projects.show', project.id)"
                    class="text-sm text-slate-500 hover:text-slate-700 font-medium">← Back to Project</a>
            </div>

            <!-- Tabs -->
            <div class="border-b border-slate-200">
                <nav class="flex gap-6 text-sm font-medium">
                    <button @click="activeTab = 'members'"
                        class="pb-2 border-b-2 transition-colors"
                        :class="activeTab === 'members' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        Members
                    </button>
                    <button v-if="can_manage_capacity" @click="openCapacityTab"
                        class="pb-2 border-b-2 transition-colors"
                        :class="activeTab === 'capacity' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        Capacity
                    </button>
                </nav>
            </div>

            <!-- ── MEMBERS TAB ─────────────────────────────────────────────── -->
            <template v-if="activeTab === 'members'">
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
            </template>

            <!-- ── CAPACITY TAB ────────────────────────────────────────────── -->
            <template v-else>
                <div class="space-y-4">
                    <!-- Month navigator -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="prevMonth" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <span class="text-sm font-semibold text-slate-700 min-w-[140px] text-center">{{ monthLabel }}</span>
                            <button @click="nextMonth" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="copyPreviousMonth"
                                class="text-xs text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">
                                Copy previous month
                            </button>
                            <button @click="saveCapacity" :disabled="capacitySaving"
                                class="text-xs bg-primary text-white font-medium px-3 py-1.5 rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                                {{ capacitySaving ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </div>

                    <!-- Capacity table -->
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <div v-if="capacityLoading" class="py-10 text-center text-sm text-slate-400">Loading…</div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500">Member</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 w-32">Available (h)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 w-28">Focus (%)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 w-28">Effective (h)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="row in capacityRows" :key="row.user_id"
                                    :class="row.dirty ? 'bg-amber-50/40' : ''">
                                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ row.user_name }}</td>
                                    <td class="px-4 py-2.5">
                                        <input v-model.number="row.available_hours" type="number" min="0" max="744" step="1"
                                            @input="markDirty(row)"
                                            class="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input v-model.number="row.focus_factor" type="number" min="0.1" max="1.0" step="0.05"
                                            @input="markDirty(row)"
                                            class="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600 font-medium">{{ row.effective_hours.toFixed(1) }} h</td>
                                    <td class="px-4 py-2.5">
                                        <input v-model="row.notes" type="text" placeholder="e.g. 3 days PTO"
                                            @input="markDirty(row)"
                                            class="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200">
                                <tr>
                                    <td class="px-4 py-2.5 text-xs font-semibold text-slate-500">Total</td>
                                    <td class="px-4 py-2.5 text-xs font-semibold text-slate-700">
                                        {{ capacityRows.reduce((s, r) => s + r.available_hours, 0).toFixed(1) }} h
                                    </td>
                                    <td></td>
                                    <td class="px-4 py-2.5 text-xs font-semibold text-primary">
                                        {{ capacityRows.reduce((s, r) => s + r.effective_hours, 0).toFixed(1) }} h
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="text-xs text-slate-400">Effective hours = available hours × focus factor. These are used to derive sprint available capacity.</p>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
