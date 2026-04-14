<script setup lang="ts">
import PriorityBadge from '@/components/PriorityBadge.vue';
import { type TaskListItem } from '@/types';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type MemberOption = { value: number; label: string };

const props = defineProps<{
    tasks: TaskListItem[];
    projectId: number;
    sprintId?: number | null;
    members: MemberOption[];
    canCreate: boolean;
    canChangeStatus: boolean;
    /** Priority filter values currently active — empty means all */
    filterPriorities: string[];
    /** Category filter value — empty means all */
    filterCategory: string;
}>();

// ── Columns ───────────────────────────────────────────────────────────────────

const STATUSES = [
    { value: 'todo',        label: 'To Do',       color: 'bg-slate-400' },
    { value: 'in_progress', label: 'In Progress',  color: 'bg-blue-500'  },
    { value: 'done',        label: 'Done',         color: 'bg-emerald-500'},
    { value: 'cancelled',   label: 'Cancelled',    color: 'bg-red-400'   },
] as const;

type StatusValue = typeof STATUSES[number]['value'];

// ── Filtering ─────────────────────────────────────────────────────────────────

const filteredTasks = computed(() => {
    return props.tasks.filter(t => {
        if (props.filterPriorities.length && !props.filterPriorities.includes(t.priority)) return false;
        if (props.filterCategory && t.category !== props.filterCategory) return false;
        return true;
    });
});

function columnTasks(status: StatusValue) {
    return filteredTasks.value.filter(t => t.status === status);
}

// ── Drag and drop ─────────────────────────────────────────────────────────────

const draggingId = ref<number | null>(null);
const dragOverStatus = ref<StatusValue | null>(null);

function onDragStart(task: TaskListItem) {
    draggingId.value = task.id;
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

    const task = props.tasks.find(t => t.id === id);
    if (!task || task.status === newStatus) return;

    router.patch(
        route('projects.tasks.status.update', { project: props.projectId, task: id }),
        { status: newStatus },
        { preserveScroll: true },
    );
}

// ── Quick-add ─────────────────────────────────────────────────────────────────

const openAddStatus = ref<StatusValue | null>(null);

const addForm = useForm({
    title:       '',
    priority:    'medium' as string,
    assignee_id: null as number | null,
    status:      '' as string,
    sprint_id:   null as number | null,
    effort_unit: 'points' as string,
});

function openAdd(status: StatusValue) {
    openAddStatus.value = status;
    addForm.reset();
    addForm.status    = status;
    addForm.sprint_id = props.sprintId ?? null;
}

function submitAdd() {
    addForm.post(route('projects.tasks.store', { project: props.projectId }), {
        preserveScroll: true,
        onSuccess: () => { openAddStatus.value = null; addForm.reset(); },
    });
}

function cancelAdd() {
    openAddStatus.value = null;
    addForm.reset();
}

// ── Helpers ───────────────────────────────────────────────────────────────────


function isOverdue(due: string | null) {
    return due ? new Date(due) < new Date(new Date().toDateString()) : false;
}

function initials(name: string) {
    return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}
</script>

<template>
    <div class="flex gap-4 overflow-x-auto pb-4">
        <div
            v-for="col in STATUSES"
            :key="col.value"
            class="flex flex-col w-72 shrink-0"
            @dragover.prevent="onDragOver(col.value)"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop(col.value)"
        >
            <!-- Column header -->
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="col.color" />
                <span class="text-sm font-semibold text-slate-700">{{ col.label }}</span>
                <span class="ml-auto text-xs font-medium text-slate-400 bg-slate-100 rounded-full px-2 py-0.5">
                    {{ columnTasks(col.value).length }}
                </span>
            </div>

            <!-- Drop zone -->
            <div
                class="flex flex-col gap-2 min-h-16 rounded-xl p-2 transition-colors"
                :class="dragOverStatus === col.value && canChangeStatus
                    ? 'bg-primary/8 ring-2 ring-primary/30'
                    : 'bg-slate-100/60'"
            >
                <!-- Task cards -->
                <div
                    v-for="task in columnTasks(col.value)"
                    :key="task.id"
                    :draggable="canChangeStatus"
                    class="bg-white border border-slate-200 rounded-lg p-3 space-y-2 shadow-sm select-none transition-opacity"
                    :class="[
                        canChangeStatus ? 'cursor-grab active:cursor-grabbing' : '',
                        draggingId === task.id ? 'opacity-40' : 'hover:border-slate-300',
                    ]"
                    @dragstart="onDragStart(task)"
                    @dragend="draggingId = null"
                >
                    <!-- Title -->
                    <Link
                        :href="route('projects.tasks.show', { project: projectId, task: task.id })"
                        class="block text-sm font-medium text-slate-800 hover:text-primary leading-snug"
                        @click.stop
                    >
                        {{ task.title }}
                    </Link>

                    <!-- Meta row -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- Priority -->
                        <PriorityBadge :priority="task.priority" :label="task.priority_label" size="xs" />
                        <!-- Category -->
                        <span v-if="task.category_label"
                            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                            {{ task.category_label }}
                        </span>
                    </div>

                    <!-- Footer: assignee + due date -->
                    <div class="flex items-center justify-between">
                        <div v-if="task.assignee"
                            class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-primary/15 text-primary text-[9px] font-bold flex items-center justify-center">
                                {{ initials(task.assignee.name) }}
                            </span>
                            <span class="text-[10px] text-slate-500">{{ task.assignee.name }}</span>
                        </div>
                        <div v-else class="flex-1" />
                        <span v-if="task.due_date"
                            class="text-[10px] font-medium"
                            :class="isOverdue(task.due_date) ? 'text-red-500' : 'text-slate-400'">
                            {{ task.due_date }}
                        </span>
                    </div>
                </div>

                <!-- Quick-add form -->
                <form v-if="openAddStatus === col.value"
                    @submit.prevent="submitAdd"
                    class="bg-white border border-primary/40 rounded-lg p-3 space-y-2 shadow-sm"
                >
                    <input
                        v-model="addForm.title"
                        type="text"
                        placeholder="Task title…"
                        required
                        autofocus
                        class="w-full text-sm border border-slate-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/40"
                        @keydown.escape="cancelAdd"
                    />
                    <div class="flex gap-2">
                        <select v-model="addForm.priority"
                            class="flex-1 text-xs border border-slate-200 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select v-if="members.length" v-model="addForm.assignee_id"
                            class="flex-1 text-xs border border-slate-200 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option :value="null">No assignee</option>
                            <option v-for="m in members" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <p v-if="addForm.errors.title" class="text-xs text-red-500">{{ addForm.errors.title }}</p>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="addForm.processing"
                            class="text-xs font-medium px-3 py-1.5 bg-primary text-white rounded-md hover:opacity-90 disabled:opacity-50">
                            Add
                        </button>
                        <button type="button" @click="cancelAdd"
                            class="text-xs text-slate-400 hover:text-slate-600 px-2">
                            Cancel
                        </button>
                    </div>
                </form>

                <!-- Add task button -->
                <button v-else-if="canCreate"
                    @click="openAdd(col.value)"
                    class="text-xs text-slate-400 hover:text-slate-600 hover:bg-white/80 rounded-lg px-3 py-2 text-left transition-colors w-full">
                    + Add task
                </button>
            </div>
        </div>
    </div>
</template>
