<script setup lang="ts">
import PriorityBadge from '@/components/PriorityBadge.vue';
import { type RequirementStatus } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface BoardItem {
    id: number;
    ref: string;
    title: string;
    status: RequirementStatus;
    priority?: string;
    priority_label?: string;
    category?: string | null;
    type_label?: string;
    assignee?: { id: number; name: string } | null;
    is_blocked?: boolean;
}

const props = defineProps<{
    items: BoardItem[];
    projectId: number;
    showRouteName: string;
    statusUpdateRouteName: string;
    canChangeStatus: boolean;
    filterPriorities: string[];
    filterType: string;
    filterCategory: string;
}>();

// ── Columns ───────────────────────────────────────────────────────────────────

const STATUSES = [
    { value: 'draft',       label: 'Draft',       color: 'bg-slate-400'  },
    { value: 'review',      label: 'In Review',   color: 'bg-blue-500'   },
    { value: 'approved',    label: 'Approved',    color: 'bg-emerald-500' },
    { value: 'implemented', label: 'Implemented', color: 'bg-violet-500' },
    { value: 'deprecated',  label: 'Deprecated',  color: 'bg-red-400'    },
] as const;

type StatusValue = typeof STATUSES[number]['value'];

// ── Filtering ─────────────────────────────────────────────────────────────────

const filteredItems = computed(() => {
    return props.items.filter(item => {
        if (props.filterPriorities.length && item.priority && !props.filterPriorities.includes(item.priority)) return false;
        if (props.filterCategory && item.category !== props.filterCategory) return false;
        if (props.filterType) {
            const itemType = (item as unknown as Record<string, string>)['type'];
            if (itemType && itemType !== props.filterType) return false;
        }
        return true;
    });
});

function columnItems(status: StatusValue) {
    return filteredItems.value.filter(i => i.status === status);
}

// ── Drag and drop ─────────────────────────────────────────────────────────────

const draggingId = ref<number | null>(null);
const dragOverStatus = ref<StatusValue | null>(null);

function onDragStart(item: BoardItem) {
    draggingId.value = item.id;
}

function onDragOver(status: StatusValue) {
    dragOverStatus.value = status;
}

function onDragLeave() {
    dragOverStatus.value = null;
}

function onDrop(newStatus: StatusValue) {
    dragOverStatus.value = null;
    const id = draggingId.value;
    draggingId.value = null;
    if (!id) return;

    const item = props.items.find(i => i.id === id);
    if (!item || item.status === newStatus) return;

    router.patch(
        route(props.statusUpdateRouteName, { project: props.projectId, [routeModelParam.value]: id }),
        { status: newStatus },
        { preserveScroll: true },
    );
}

// Derive the route model parameter name from the show/status route names
const routeModelParam = computed(() => {
    if (props.showRouteName.includes('business')) return 'businessRequirement';
    if (props.showRouteName.includes('technical')) return 'technicalRequirement';
    return 'testCase';
});

// ── Helpers ───────────────────────────────────────────────────────────────────


function initials(name: string) {
    return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}
</script>

<template>
    <div class="flex gap-4 overflow-x-auto pb-4">
        <div
            v-for="col in STATUSES"
            :key="col.value"
            class="flex flex-col w-64 shrink-0"
            @dragover.prevent="onDragOver(col.value)"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop(col.value)"
        >
            <!-- Column header -->
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="col.color" />
                <span class="text-sm font-semibold text-slate-700">{{ col.label }}</span>
                <span class="ml-auto text-xs font-medium text-slate-400 bg-slate-100 rounded-full px-2 py-0.5">
                    {{ columnItems(col.value).length }}
                </span>
            </div>

            <!-- Drop zone -->
            <div
                class="flex flex-col gap-2 min-h-16 rounded-xl p-2 transition-colors"
                :class="dragOverStatus === col.value && canChangeStatus
                    ? 'bg-primary/8 ring-2 ring-primary/30'
                    : 'bg-slate-100/60'"
            >
                <!-- Item cards -->
                <div
                    v-for="item in columnItems(col.value)"
                    :key="item.id"
                    :draggable="canChangeStatus"
                    class="bg-white border border-slate-200 rounded-lg p-3 space-y-2 shadow-sm select-none transition-opacity"
                    :class="[
                        canChangeStatus ? 'cursor-grab active:cursor-grabbing' : '',
                        draggingId === item.id ? 'opacity-40' : 'hover:border-slate-300',
                    ]"
                    @dragstart="onDragStart(item)"
                    @dragend="draggingId = null"
                >
                    <!-- Ref + blocked badge -->
                    <div class="flex items-center gap-1.5">
                        <span class="font-mono text-[10px] text-slate-400">{{ item.ref }}</span>
                        <span v-if="item.is_blocked"
                            class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">
                            Blocked
                        </span>
                    </div>

                    <!-- Title -->
                    <Link
                        :href="route(showRouteName, { project: projectId, [routeModelParam]: item.id })"
                        class="block text-sm font-medium text-slate-800 hover:text-primary leading-snug"
                        @click.stop
                    >
                        {{ item.title }}
                    </Link>

                    <!-- Meta row -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- Priority -->
                        <PriorityBadge v-if="item.priority" :priority="item.priority" :label="item.priority_label ?? ''" size="xs" />
                        <!-- Type -->
                        <span v-if="item.type_label"
                            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-sky-100 text-sky-700">
                            {{ item.type_label }}
                        </span>
                        <!-- Category -->
                        <span v-if="item.category"
                            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                            {{ item.category }}
                        </span>
                    </div>

                    <!-- Assignee (TC) -->
                    <div v-if="item.assignee" class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-primary/15 text-primary text-[9px] font-bold flex items-center justify-center">
                            {{ initials(item.assignee.name) }}
                        </span>
                        <span class="text-[10px] text-slate-500">{{ item.assignee.name }}</span>
                    </div>
                </div>

                <!-- Empty column placeholder -->
                <div v-if="columnItems(col.value).length === 0"
                    class="flex-1 flex items-center justify-center text-xs text-slate-300 py-4">
                    Empty
                </div>
            </div>
        </div>
    </div>
</template>
