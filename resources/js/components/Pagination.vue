<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginator } from '@/types';

const props = defineProps<{
    paginator: Paginator<unknown>;
}>();

// links[0] = "Previous", links[last] = "Next" — slice them off, use prev/next_page_url directly
const pageLinks = computed(() => props.paginator.links.slice(1, -1));
</script>

<template>
    <div v-if="paginator.last_page > 1 || paginator.total > 0"
        class="flex items-center justify-between py-3 px-1 border-t border-slate-100">

        <p class="text-xs text-slate-400">
            <template v-if="paginator.total > 0 && paginator.from">
                Showing {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
            </template>
            <template v-else-if="paginator.total === 0">No results</template>
        </p>

        <div v-if="paginator.last_page > 1" class="flex items-center gap-1">
            <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" preserve-scroll
                class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                ← Prev
            </Link>
            <span v-else class="px-2.5 py-1 text-xs rounded-lg border border-slate-100 text-slate-300 select-none">← Prev</span>

            <template v-for="link in pageLinks" :key="link.label">
                <span v-if="!link.url" class="px-1.5 text-xs text-slate-400 select-none">…</span>
                <span v-else-if="link.active"
                    class="px-2.5 py-1 text-xs rounded-lg bg-primary text-primary-foreground font-medium select-none">
                    {{ link.label }}
                </span>
                <Link v-else :href="link.url" preserve-scroll
                    class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ link.label }}
                </Link>
            </template>

            <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" preserve-scroll
                class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                Next →
            </Link>
            <span v-else class="px-2.5 py-1 text-xs rounded-lg border border-slate-100 text-slate-300 select-none">Next →</span>
        </div>
    </div>
</template>
