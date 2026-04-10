<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();
const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <Link :href="route('notifications.index')" class="relative p-2 rounded-md hover:bg-sidebar-accent transition">
                <Bell class="w-5 h-5 text-sidebar-foreground/70" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Link>
        </div>
    </header>
</template>
