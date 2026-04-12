<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

type PermissionItem = { id: number; key: string; label: string };
type PermissionGroup = { group: string; permissions: PermissionItem[] };

const props = defineProps<{
    role: { id: number; name: string };
    groups: PermissionGroup[];
    assigned: number[];
    submitRoute: string;
    readonly?: boolean;
    readonlyMessage?: string;
}>();

const form = useForm({ permissions: [...props.assigned] });

function toggle(id: number) {
    if (props.readonly) return;
    const idx = form.permissions.indexOf(id);
    if (idx === -1) form.permissions.push(id);
    else form.permissions.splice(idx, 1);
}

function toggleGroup(group: PermissionGroup) {
    if (props.readonly) return;
    const ids = group.permissions.map(p => p.id);
    const allOn = ids.every(id => form.permissions.includes(id));
    if (allOn) {
        form.permissions = form.permissions.filter(id => !ids.includes(id));
    } else {
        ids.forEach(id => { if (!form.permissions.includes(id)) form.permissions.push(id); });
    }
}

function groupAllChecked(group: PermissionGroup) {
    return group.permissions.every(p => form.permissions.includes(p.id));
}

function groupPartiallyChecked(group: PermissionGroup) {
    return group.permissions.some(p => form.permissions.includes(p.id)) && !groupAllChecked(group);
}

function save() {
    form.put(props.submitRoute);
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ role.name }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    <span v-if="readonly">{{ readonlyMessage ?? 'This role is read-only.' }}</span>
                    <span v-else>Configure what this role can view and do across the application.</span>
                </p>
            </div>
            <button v-if="!readonly" @click="save" :disabled="form.processing"
                class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 disabled:opacity-50 transition">
                Save Changes
            </button>
        </div>

        <div v-if="readonly" class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
            {{ readonlyMessage ?? 'This role is read-only.' }}
        </div>

        <div class="space-y-4">
            <div v-for="group in groups" :key="group.group"
                class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border-b border-slate-200">
                    <input type="checkbox"
                        :checked="readonly || groupAllChecked(group)"
                        :indeterminate="!readonly && groupPartiallyChecked(group)"
                        :disabled="readonly"
                        @change="toggleGroup(group)"
                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer disabled:cursor-default" />
                    <span class="text-sm font-semibold text-slate-700">{{ group.group }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0 divide-y divide-slate-100 sm:divide-y-0">
                    <label v-for="perm in group.permissions" :key="perm.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 cursor-pointer"
                        :class="{ 'cursor-default': readonly }">
                        <input type="checkbox"
                            :checked="readonly || form.permissions.includes(perm.id)"
                            :disabled="readonly"
                            @change="toggle(perm.id)"
                            class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer disabled:cursor-default" />
                        <span class="text-sm text-slate-700">{{ perm.label }}</span>
                    </label>
                </div>
            </div>
        </div>

        <div v-if="!readonly" class="flex justify-end">
            <button @click="save" :disabled="form.processing"
                class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 disabled:opacity-50 transition">
                Save Changes
            </button>
        </div>
    </div>
</template>
