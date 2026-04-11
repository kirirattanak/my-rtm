<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import Toast from '@/components/Toast.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <Transition name="page" mode="out-in">
                <div :key="page.url">
                    <slot />
                </div>
            </Transition>
        </AppContent>
    </AppShell>
    <Toast />
</template>

<style scoped>
.page-enter-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.page-leave-active {
    transition: opacity 0.12s ease;
}
.page-enter-from {
    opacity: 0;
    transform: translateY(6px);
}
.page-leave-to {
    opacity: 0;
}
</style>
