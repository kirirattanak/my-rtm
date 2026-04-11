<script setup lang="ts">
import { computed, ref } from 'vue';

interface GanttTask {
    id: number;
    title: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    start_date: string | null;
    end_date: string | null;
    due_date: string | null;
    assignee: { id: number; name: string } | null;
    sprint: { id: number; name: string } | null;
}

const props = defineProps<{
    tasks: GanttTask[];
    projectId: number;
}>();

type ViewMode = 'week' | 'month';
const viewMode = ref<ViewMode>('month');

const DAY_WIDTH: Record<ViewMode, number> = { week: 32, month: 12 };

// ── Date helpers ────────────────────────────────────────────────
function parseDate(s: string): Date {
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function addDays(d: Date, n: number): Date {
    const r = new Date(d);
    r.setDate(r.getDate() + n);
    return r;
}

function dayDiff(a: Date, b: Date): number {
    return Math.round((b.getTime() - a.getTime()) / 86_400_000);
}

function fmtDate(d: Date): string {
    return d.toISOString().slice(0, 10);
}

// ── Scheduled vs unscheduled ─────────────────────────────────────
const scheduled = computed(() => props.tasks.filter(t => t.start_date && t.end_date));
const unscheduled = computed(() => props.tasks.filter(t => !t.start_date || !t.end_date));

// ── Range ────────────────────────────────────────────────────────
const rangeStart = computed<Date>(() => {
    if (scheduled.value.length === 0) {
        const d = new Date();
        d.setDate(1);
        return d;
    }
    const min = scheduled.value.reduce<Date>((acc, t) => {
        const d = parseDate(t.start_date!);
        return d < acc ? d : acc;
    }, parseDate(scheduled.value[0].start_date!));
    // pad 3 days before
    return addDays(min, -3);
});

const rangeEnd = computed<Date>(() => {
    if (scheduled.value.length === 0) {
        const d = new Date();
        d.setMonth(d.getMonth() + 2);
        d.setDate(0);
        return d;
    }
    const max = scheduled.value.reduce<Date>((acc, t) => {
        const d = parseDate(t.end_date!);
        return d > acc ? d : acc;
    }, parseDate(scheduled.value[0].end_date!));
    // pad 3 days after
    return addDays(max, 3);
});

const totalDays = computed(() => dayDiff(rangeStart.value, rangeEnd.value) + 1);
const totalWidth = computed(() => totalDays.value * DAY_WIDTH[viewMode.value]);

// ── Header: months + week markers ───────────────────────────────
interface MonthSpan { label: string; days: number; }
const monthSpans = computed<MonthSpan[]>(() => {
    const spans: MonthSpan[] = [];
    let cursor = new Date(rangeStart.value);
    const end = rangeEnd.value;

    while (cursor <= end) {
        const month = cursor.getMonth();
        const year = cursor.getFullYear();
        let days = 0;
        while (cursor <= end && cursor.getMonth() === month && cursor.getFullYear() === year) {
            cursor = addDays(cursor, 1);
            days++;
        }
        spans.push({ label: new Date(year, month, 1).toLocaleString('default', { month: 'short', year: 'numeric' }), days });
    }
    return spans;
});

// Days in header row (one per day, only shown for week view — month view shows every 7th)
interface DayCell { label: string; isToday: boolean; isWeekStart: boolean; }
const dayCells = computed<DayCell[]>(() => {
    const cells: DayCell[] = [];
    const todayStr = fmtDate(new Date());
    for (let i = 0; i < totalDays.value; i++) {
        const d = addDays(rangeStart.value, i);
        cells.push({
            label: String(d.getDate()),
            isToday: fmtDate(d) === todayStr,
            isWeekStart: d.getDay() === 1,
        });
    }
    return cells;
});

// Today offset
const todayOffset = computed(() => {
    const todayStr = fmtDate(new Date());
    const todayDate = parseDate(todayStr);
    const diff = dayDiff(rangeStart.value, todayDate);
    if (diff < 0 || diff >= totalDays.value) return null;
    return diff * DAY_WIDTH[viewMode.value];
});

// ── Bar positioning ──────────────────────────────────────────────
function barStyle(task: GanttTask): Record<string, string> {
    const start = parseDate(task.start_date!);
    const end = parseDate(task.end_date!);
    const dw = DAY_WIDTH[viewMode.value];
    const left = dayDiff(rangeStart.value, start) * dw;
    const width = Math.max((dayDiff(start, end) + 1) * dw, dw);
    return { left: left + 'px', width: width + 'px' };
}

const priorityBarClass: Record<string, string> = {
    critical: 'bg-red-400',
    high:     'bg-orange-400',
    medium:   'bg-amber-400',
    low:      'bg-slate-300',
};

// ── Tooltip ──────────────────────────────────────────────────────
const tooltip = ref<{ task: GanttTask; x: number; y: number } | null>(null);

function showTooltip(e: MouseEvent, task: GanttTask) {
    tooltip.value = { task, x: e.clientX, y: e.clientY };
}
function moveTooltip(e: MouseEvent) {
    if (tooltip.value) { tooltip.value.x = e.clientX; tooltip.value.y = e.clientY; }
}
function hideTooltip() { tooltip.value = null; }
</script>

<template>
    <div class="select-none">

        <!-- Controls -->
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs text-slate-500 font-medium">View:</span>
            <div class="flex rounded-lg border border-slate-200 overflow-hidden text-xs font-medium">
                <button
                    v-for="mode in (['month', 'week'] as ViewMode[])" :key="mode"
                    @click="viewMode = mode"
                    :class="viewMode === mode
                        ? 'bg-primary text-white px-3 py-1'
                        : 'bg-white text-slate-600 hover:bg-slate-50 px-3 py-1 transition'">
                    {{ mode.charAt(0).toUpperCase() + mode.slice(1) }}
                </button>
            </div>
            <div class="ml-4 flex items-center gap-3 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block" /> Critical</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-orange-400 inline-block" /> High</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-400 inline-block" /> Medium</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-slate-300 inline-block" /> Low</span>
            </div>
        </div>

        <!-- Chart -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="flex">
                <!-- Sidebar -->
                <div class="w-56 flex-shrink-0 border-r border-slate-200 z-10 bg-white">
                    <!-- Sidebar header (aligns with month row + day row) -->
                    <div class="border-b border-slate-200 bg-slate-50">
                        <div class="px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wide h-7 flex items-center">Task</div>
                        <div class="h-6 border-t border-slate-100" />
                    </div>
                    <!-- Sidebar rows -->
                    <div v-for="task in scheduled" :key="task.id"
                        class="flex items-center px-3 h-10 border-b border-slate-100 last:border-0"
                        :class="{ 'opacity-40': task.status === 'cancelled', 'opacity-60': task.status === 'done' }">
                        <a :href="route('projects.tasks.show', [projectId, task.id])"
                            class="text-xs text-slate-800 font-medium truncate hover:text-primary transition"
                            :class="{ 'line-through': task.status === 'done' }">
                            {{ task.title }}
                        </a>
                    </div>
                </div>

                <!-- Scrollable gantt area -->
                <div class="overflow-x-auto flex-1 min-w-0">
                    <div :style="{ width: totalWidth + 'px', minWidth: '100%' }" class="relative">

                        <!-- Month header -->
                        <div class="flex h-7 border-b border-slate-200 bg-slate-50">
                            <div v-for="(span, i) in monthSpans" :key="i"
                                class="text-xs font-semibold text-slate-600 px-2 flex items-center border-r border-slate-200 last:border-0 truncate"
                                :style="{ width: span.days * DAY_WIDTH[viewMode] + 'px', flexShrink: 0 }">
                                {{ span.label }}
                            </div>
                        </div>

                        <!-- Day header -->
                        <div class="flex h-6 border-b border-slate-200 bg-slate-50">
                            <div v-for="(cell, i) in dayCells" :key="i"
                                class="flex items-center justify-center text-[10px] border-r border-slate-100 last:border-0 flex-shrink-0"
                                :style="{ width: DAY_WIDTH[viewMode] + 'px' }"
                                :class="[
                                    cell.isToday ? 'bg-primary/10 text-primary font-bold' : 'text-slate-400',
                                    viewMode === 'month' && !cell.isWeekStart && !cell.isToday ? 'invisible' : '',
                                ]">
                                {{ cell.label }}
                            </div>
                        </div>

                        <!-- Task rows -->
                        <div class="relative">
                            <!-- Grid columns (week separators) -->
                            <div class="absolute inset-0 flex pointer-events-none">
                                <div v-for="(cell, i) in dayCells" :key="i"
                                    class="flex-shrink-0 border-r"
                                    :style="{ width: DAY_WIDTH[viewMode] + 'px' }"
                                    :class="cell.isWeekStart ? 'border-slate-200' : 'border-slate-100'" />
                            </div>

                            <!-- Today line -->
                            <div v-if="todayOffset !== null"
                                class="absolute top-0 bottom-0 w-px bg-primary/60 pointer-events-none z-10"
                                :style="{ left: todayOffset + 'px' }" />

                            <!-- Bars -->
                            <div v-for="task in scheduled" :key="task.id"
                                class="relative h-10 border-b border-slate-100 last:border-0">
                                <div
                                    class="absolute top-1/2 -translate-y-1/2 h-5 rounded cursor-pointer transition-opacity hover:opacity-80"
                                    :class="[
                                        priorityBarClass[task.priority],
                                        { 'opacity-40': task.status === 'cancelled', 'opacity-60': task.status === 'done' },
                                    ]"
                                    :style="barStyle(task)"
                                    @mouseenter="showTooltip($event, task)"
                                    @mousemove="moveTooltip"
                                    @mouseleave="hideTooltip"
                                    @click="$inertia.visit(route('projects.tasks.show', [projectId, task.id]))" />
                            </div>

                            <!-- Empty state -->
                            <div v-if="scheduled.length === 0"
                                class="h-24 flex items-center justify-center text-sm text-slate-400">
                                No tasks with start and end dates set.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unscheduled tasks -->
            <div v-if="unscheduled.length > 0" class="border-t border-slate-200">
                <div class="px-4 py-2 bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    Unscheduled ({{ unscheduled.length }})
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="task in unscheduled" :key="task.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition">
                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                            :class="priorityBarClass[task.priority].replace('bg-', 'bg-')" />
                        <a :href="route('projects.tasks.show', [projectId, task.id])"
                            class="text-sm text-slate-800 hover:text-primary font-medium truncate">
                            {{ task.title }}
                        </a>
                        <span class="text-xs text-slate-400 ml-auto flex-shrink-0">{{ task.due_date ? 'Due ' + task.due_date : 'No dates' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tooltip -->
        <Teleport to="body">
            <div v-if="tooltip"
                class="fixed z-50 pointer-events-none bg-slate-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl space-y-1"
                :style="{ top: tooltip.y - 8 + 'px', left: tooltip.x + 16 + 'px', transform: 'translateY(-100%)' }">
                <div class="font-semibold">{{ tooltip.task.title }}</div>
                <div class="text-slate-300">{{ tooltip.task.start_date }} → {{ tooltip.task.end_date }}</div>
                <div class="flex items-center gap-2 text-slate-300">
                    <span>{{ tooltip.task.priority_label }}</span>
                    <span>·</span>
                    <span>{{ tooltip.task.status_label }}</span>
                    <span v-if="tooltip.task.assignee">· {{ tooltip.task.assignee.name }}</span>
                </div>
            </div>
        </Teleport>
    </div>
</template>
