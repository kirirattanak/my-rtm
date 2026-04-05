<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PaginatorMeta, PaginatorLinks } from '@/types';

const props = defineProps<{
    meta: PaginatorMeta;
    links: PaginatorLinks;
}>();

// meta.links includes "Previous" at [0] and "Next" at [end]; slice those off
const pageLinks = computed(() => props.meta.links.slice(1, -1));
</script>

<template>
    <div v-if="meta.last_page > 1 || meta.total > 0"
        class="flex items-center justify-between py-3 px-1 border-t border-slate-100 mt-0">

        <p class="text-xs text-slate-400">
            <template v-if="meta.total > 0 && meta.from">
                Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
            </template>
            <template v-else-if="meta.total === 0">No results</template>
        </p>

        <div v-if="meta.last_page > 1" class="flex items-center gap-1">
            <Link v-if="links.prev" :href="links.prev" preserve-scroll
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

            <Link v-if="links.next" :href="links.next" preserve-scroll
                class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                Next →
            </Link>
            <span v-else class="px-2.5 py-1 text-xs rounded-lg border border-slate-100 text-slate-300 select-none">Next →</span>
        </div>
    </div>
</template>
