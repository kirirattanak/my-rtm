<script setup lang="ts">
defineProps<{
    priority: string;
    label: string;
    /** 'sm' (default) = pill badge; 'xs' = compact kanban card variant */
    size?: 'xs' | 'sm';
}>();

const COLOR_CLASSES: Record<string, string> = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-slate-100 text-slate-500',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1 font-medium rounded-full"
        :class="[
            COLOR_CLASSES[priority] ?? 'bg-slate-100 text-slate-500',
            size === 'xs' ? 'text-[10px] px-1.5 py-0.5' : 'text-xs px-2 py-0.5',
        ]"
    >
        <!-- Critical: double chevron up -->
        <svg v-if="priority === 'critical'" :class="size === 'xs' ? 'w-2.5 h-2.5' : 'w-3 h-3'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="17 11 12 6 7 11" />
            <polyline points="17 18 12 13 7 18" />
        </svg>
        <!-- High: single chevron up -->
        <svg v-else-if="priority === 'high'" :class="size === 'xs' ? 'w-2.5 h-2.5' : 'w-3 h-3'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15" />
        </svg>
        <!-- Medium: horizontal bar -->
        <svg v-else-if="priority === 'medium'" :class="size === 'xs' ? 'w-2.5 h-2.5' : 'w-3 h-3'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        <!-- Low: single chevron down -->
        <svg v-else-if="priority === 'low'" :class="size === 'xs' ? 'w-2.5 h-2.5' : 'w-3 h-3'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9" />
        </svg>

        {{ label }}
    </span>
</template>
