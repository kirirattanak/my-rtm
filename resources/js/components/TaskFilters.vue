<script setup lang="ts">
const props = defineProps<{
    priorities: string[];    // currently selected
    category: string;        // currently selected, '' = all
    availableCategories: { value: string; label: string }[];
}>();

const emit = defineEmits<{
    'update:priorities': [value: string[]];
    'update:category':   [value: string];
}>();

const ALL_PRIORITIES = [
    { value: 'critical', label: 'Critical', icon: 'critical', class: 'bg-red-100 text-red-700 ring-red-300'         },
    { value: 'high',     label: 'High',     icon: 'high',     class: 'bg-orange-100 text-orange-700 ring-orange-300' },
    { value: 'medium',   label: 'Medium',   icon: 'medium',   class: 'bg-amber-100 text-amber-700 ring-amber-300'    },
    { value: 'low',      label: 'Low',      icon: 'low',      class: 'bg-slate-100 text-slate-500 ring-slate-300'    },
];

function togglePriority(value: string) {
    const next = props.priorities.includes(value)
        ? props.priorities.filter(p => p !== value)
        : [...props.priorities, value];
    emit('update:priorities', next);
}

function isActive(value: string) {
    return props.priorities.includes(value);
}
</script>

<template>
    <div class="flex items-center gap-3 flex-wrap">
        <!-- Priority pills -->
        <div class="flex items-center gap-1.5">
            <span class="text-xs text-slate-500 font-medium mr-0.5">Priority:</span>
            <button
                v-for="p in ALL_PRIORITIES"
                :key="p.value"
                type="button"
                @click="togglePriority(p.value)"
                class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full transition-all ring-1"
                :class="isActive(p.value)
                    ? [p.class, 'ring-2 ring-offset-1']
                    : 'bg-white text-slate-400 ring-slate-200 hover:ring-slate-300'"
            >
                <!-- Critical: double chevron up -->
                <svg v-if="p.icon === 'critical'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 11 12 6 7 11" /><polyline points="17 18 12 13 7 18" />
                </svg>
                <!-- High: chevron up -->
                <svg v-else-if="p.icon === 'high'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15" />
                </svg>
                <!-- Medium: horizontal bar -->
                <svg v-else-if="p.icon === 'medium'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <!-- Low: chevron down -->
                <svg v-else-if="p.icon === 'low'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
                {{ p.label }}
            </button>
        </div>

        <!-- Category dropdown -->
        <div class="flex items-center gap-1.5">
            <span class="text-xs text-slate-500 font-medium">Category:</span>
            <select
                :value="category"
                @change="emit('update:category', ($event.target as HTMLSelectElement).value)"
                class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/40"
            >
                <option value="">All</option>
                <option v-for="c in availableCategories" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
        </div>

        <!-- Clear button -->
        <button
            v-if="priorities.length || category"
            type="button"
            @click="emit('update:priorities', []); emit('update:category', '')"
            class="text-xs text-slate-400 hover:text-slate-600 underline"
        >
            Clear filters
        </button>
    </div>
</template>
