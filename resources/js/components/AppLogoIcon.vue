<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <!--
        RTM logo mark — a 3×3 matrix with the main diagonal highlighted.
        The diagonal trace represents requirement traceability through the matrix.
        Uses currentColor so it adapts to any background context.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Row 0 -->
        <rect x="1.5" y="1.5"  width="6" height="6" rx="1.5" fill="currentColor" />
        <rect x="9"   y="1.5"  width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <rect x="16.5" y="1.5" width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <!-- Row 1 -->
        <rect x="1.5" y="9"   width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <rect x="9"   y="9"   width="6" height="6" rx="1.5" fill="currentColor" />
        <rect x="16.5" y="9"  width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <!-- Row 2 -->
        <rect x="1.5"  y="16.5" width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <rect x="9"    y="16.5" width="6" height="6" rx="1.5" fill="currentColor" opacity="0.25" />
        <rect x="16.5" y="16.5" width="6" height="6" rx="1.5" fill="currentColor" />
    </svg>
</template>
