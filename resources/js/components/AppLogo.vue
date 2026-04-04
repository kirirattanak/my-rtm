<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{ class?: string }>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground flex-shrink-0">
        <AppLogoIcon class="size-5 text-white" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-bold tracking-wide">RTM</span>
        <span class="truncate text-[10px] opacity-60 font-normal">Traceability</span>
    </div>
</template>
