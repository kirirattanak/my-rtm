<script setup lang="ts">
import { computed, ref } from 'vue';

interface CalendarTask {
    id: number;
    title: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    category: string | null;
    category_label: string | null;
    due_date: string | null;
    assignee: { id: number; name: string } | null;
}

const props = defineProps<{
    tasks: CalendarTask[];
    projectId: number;
}>();

// ── State ────────────────────────────────────────────────────────
type ViewMode = 'month' | 'week';
const viewMode = ref<ViewMode>('month');
const today = new Date();
const cursor = ref(new Date(today.getFullYear(), today.getMonth(), today.getDate()));

// ── Navigation ───────────────────────────────────────────────────
function prev() {
    if (viewMode.value === 'month') {
        cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() - 1, 1);
    } else {
        const d = new Date(cursor.value);
        d.setDate(d.getDate() - 7);
        cursor.value = d;
    }
}
function next() {
    if (viewMode.value === 'month') {
        cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 1);
    } else {
        const d = new Date(cursor.value);
        d.setDate(d.getDate() + 7);
        cursor.value = d;
    }
}
function goToday() {
    cursor.value = new Date(today.getFullYear(), today.getMonth(), today.getDate());
}

// ── Helpers ──────────────────────────────────────────────────────
function fmt(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
const todayStr = fmt(today);

function isToday(d: Date) { return fmt(d) === todayStr; }

// Monday of the week containing `d`
function weekStart(d: Date): Date {
    const day = d.getDay(); // 0=Sun
    const diff = day === 0 ? -6 : 1 - day;
    const r = new Date(d);
    r.setDate(r.getDate() + diff);
    return r;
}

// ── Task lookup by date string ────────────────────────────────────
const tasksByDate = computed(() => {
    const map: Record<string, CalendarTask[]> = {};
    for (const t of props.tasks) {
        if (!t.due_date) continue;
        (map[t.due_date] ??= []).push(t);
    }
    return map;
});

const undatedTasks = computed(() => props.tasks.filter(t => !t.due_date));

// ── Month view ───────────────────────────────────────────────────
interface CalendarDay {
    date: Date;
    dateStr: string;
    inMonth: boolean;
}

const monthTitle = computed(() =>
    cursor.value.toLocaleString('default', { month: 'long', year: 'numeric' })
);

const monthDays = computed<CalendarDay[]>(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay  = new Date(year, month + 1, 0);

    // Pad to Monday
    const startPad = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    const endPad   = lastDay.getDay() === 0 ? 0 : 7 - lastDay.getDay();

    const days: CalendarDay[] = [];
    for (let i = startPad; i > 0; i--) {
        const d = new Date(year, month, 1 - i);
        days.push({ date: d, dateStr: fmt(d), inMonth: false });
    }
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(year, month, i);
        days.push({ date: d, dateStr: fmt(d), inMonth: true });
    }
    for (let i = 1; i <= endPad; i++) {
        const d = new Date(year, month + 1, i);
        days.push({ date: d, dateStr: fmt(d), inMonth: false });
    }
    return days;
});

// ── Week view ────────────────────────────────────────────────────
interface WeekDay {
    date: Date;
    dateStr: string;
}

const weekTitle = computed(() => {
    const start = weekStart(cursor.value);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const opts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    if (start.getFullYear() !== end.getFullYear()) {
        return `${start.toLocaleDateString('default', { ...opts, year: 'numeric' })} – ${end.toLocaleDateString('default', { ...opts, year: 'numeric' })}`;
    }
    if (start.getMonth() !== end.getMonth()) {
        return `${start.toLocaleDateString('default', opts)} – ${end.toLocaleDateString('default', { ...opts, year: 'numeric' })}`;
    }
    return `${start.toLocaleDateString('default', opts)} – ${end.getDate()}, ${end.getFullYear()}`;
});

const weekDays = computed<WeekDay[]>(() => {
    const start = weekStart(cursor.value);
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        return { date: d, dateStr: fmt(d) };
    });
});

// ── Pill styling ─────────────────────────────────────────────────
const pillClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-700 border-red-300',
    high:     'bg-orange-100 text-orange-700 border-orange-300',
    medium:   'bg-amber-100 text-amber-700 border-amber-300',
    low:      'bg-slate-100 text-slate-600 border-slate-300',
};

const DAY_NAMES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex items-center gap-3">
            <!-- Prev / Today / Next -->
            <div class="flex items-center gap-1">
                <button @click="prev"
                    class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button @click="goToday"
                    class="px-3 py-1.5 text-xs font-medium border border-slate-200 rounded-lg hover:bg-slate-50 transition text-slate-700">
                    Today
                </button>
                <button @click="next"
                    class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Title -->
            <h2 class="text-sm font-semibold text-slate-800 min-w-40">
                {{ viewMode === 'month' ? monthTitle : weekTitle }}
            </h2>

            <!-- View toggle -->
            <div class="ml-auto flex rounded-lg border border-slate-200 overflow-hidden text-xs font-medium">
                <button v-for="mode in (['month', 'week'] as ViewMode[])" :key="mode"
                    @click="viewMode = mode"
                    :class="viewMode === mode
                        ? 'bg-primary text-white px-3 py-1.5'
                        : 'bg-white text-slate-600 hover:bg-slate-50 px-3 py-1.5 transition'">
                    {{ mode.charAt(0).toUpperCase() + mode.slice(1) }}
                </button>
            </div>
        </div>

        <!-- ── Month view ── -->
        <div v-if="viewMode === 'month'"
            class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <!-- Day-of-week header -->
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                <div v-for="name in DAY_NAMES" :key="name"
                    class="py-2 text-center text-xs font-semibold text-slate-500">
                    {{ name }}
                </div>
            </div>
            <!-- Day cells -->
            <div class="grid grid-cols-7">
                <div v-for="(day, i) in monthDays" :key="day.dateStr"
                    class="min-h-24 p-1.5 border-b border-r border-slate-100 last:border-r-0"
                    :class="[
                        !day.inMonth ? 'bg-slate-50' : '',
                        (i + 1) % 7 === 0 ? 'border-r-0' : '',
                        i >= monthDays.length - 7 ? 'border-b-0' : '',
                    ]">
                    <!-- Date number -->
                    <div class="mb-1 flex justify-end">
                        <span class="text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full"
                            :class="isToday(day.date)
                                ? 'bg-primary text-white'
                                : day.inMonth ? 'text-slate-700' : 'text-slate-300'">
                            {{ day.date.getDate() }}
                        </span>
                    </div>
                    <!-- Task pills -->
                    <div class="space-y-0.5">
                        <a v-for="task in (tasksByDate[day.dateStr] ?? [])" :key="task.id"
                            :href="route('projects.tasks.show', [projectId, task.id])"
                            class="block truncate text-[11px] font-medium px-1.5 py-0.5 rounded border cursor-pointer hover:opacity-80 transition"
                            :class="[
                                pillClass[task.priority],
                                task.status === 'done' ? 'opacity-50 line-through' : '',
                                task.status === 'cancelled' ? 'opacity-30' : '',
                            ]"
                            :title="`${task.title} · ${task.priority_label}${task.assignee ? ' · ' + task.assignee.name : ''}`">
                            {{ task.title }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Week view ── -->
        <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="grid grid-cols-7 divide-x divide-slate-100">
                <div v-for="day in weekDays" :key="day.dateStr" class="flex flex-col">
                    <!-- Day header -->
                    <div class="py-2 text-center border-b border-slate-200"
                        :class="isToday(day.date) ? 'bg-primary/5' : 'bg-slate-50'">
                        <div class="text-xs font-semibold text-slate-500">
                            {{ day.date.toLocaleString('default', { weekday: 'short' }) }}
                        </div>
                        <div class="mt-0.5 mx-auto w-7 h-7 flex items-center justify-center rounded-full text-sm font-semibold"
                            :class="isToday(day.date) ? 'bg-primary text-white' : 'text-slate-700'">
                            {{ day.date.getDate() }}
                        </div>
                    </div>
                    <!-- Tasks -->
                    <div class="flex-1 p-1.5 space-y-1 min-h-48">
                        <a v-for="task in (tasksByDate[day.dateStr] ?? [])" :key="task.id"
                            :href="route('projects.tasks.show', [projectId, task.id])"
                            class="block rounded-lg border px-2 py-1.5 cursor-pointer hover:opacity-80 transition"
                            :class="[
                                pillClass[task.priority],
                                task.status === 'done' ? 'opacity-50' : '',
                                task.status === 'cancelled' ? 'opacity-30' : '',
                            ]">
                            <div class="text-[11px] font-semibold truncate"
                                :class="task.status === 'done' ? 'line-through' : ''">
                                {{ task.title }}
                            </div>
                            <div v-if="task.assignee || task.category_label"
                                class="text-[10px] mt-0.5 opacity-70 truncate">
                                {{ [task.category_label, task.assignee?.name].filter(Boolean).join(' · ') }}
                            </div>
                        </a>
                        <div v-if="!(tasksByDate[day.dateStr] ?? []).length"
                            class="h-full flex items-center justify-center text-xs text-slate-300 py-4">
                            —
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Undated tasks -->
        <div v-if="undatedTasks.length > 0"
            class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                No due date ({{ undatedTasks.length }})
            </div>
            <div class="flex flex-wrap gap-2 p-3">
                <a v-for="task in undatedTasks" :key="task.id"
                    :href="route('projects.tasks.show', [projectId, task.id])"
                    class="inline-flex items-center text-[11px] font-medium px-2 py-1 rounded border hover:opacity-80 transition truncate max-w-48"
                    :class="pillClass[task.priority]">
                    {{ task.title }}
                </a>
            </div>
        </div>
    </div>
</template>
