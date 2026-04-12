<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { type AppNotification, type BreadcrumbItem, type Paginator } from '@/types';
import { Head, router } from '@inertiajs/vue3';

defineProps<{
    notifications: Paginator<AppNotification>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: route('notifications.index') },
];

function notificationText(n: AppNotification): string {
    if (n.type === 'task_assigned') {
        return `${n.data.assigner} assigned you to "${n.data.task_title}" in ${n.data.project_name}`;
    }
    if (n.type === 'comment_added') {
        return `${n.data.commenter} commented on "${n.data.subject_title}": ${n.data.excerpt}`;
    }
    return 'You have a new notification.';
}

function markRead(id: string) {
    router.patch(route('notifications.read', id), {}, { preserveScroll: true });
}

function markAllRead() {
    router.patch(route('notifications.read-all'), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Notifications</h1>
                </div>
                <button
                    v-if="notifications.total > 0"
                    @click="markAllRead"
                    class="text-sm text-blue-600 hover:underline"
                >
                    Mark all as read
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="notifications.data.length === 0" class="text-center py-16 text-slate-400">
                <p class="text-lg font-medium">No notifications yet</p>
                <p class="text-sm mt-1">You'll see task assignments and comments here.</p>
            </div>

            <!-- Notification list -->
            <div v-else class="space-y-2">
                <div
                    v-for="n in notifications.data"
                    :key="n.id"
                    class="flex items-start gap-3 p-4 rounded-lg border transition"
                    :class="n.read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50'"
                >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-800">{{ notificationText(n) }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ n.created_at }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            v-if="!n.read_at"
                            @click="markRead(n.id)"
                            class="text-xs text-blue-600 hover:underline whitespace-nowrap"
                        >
                            Mark read
                        </button>
                        <a
                            v-if="n.data.url"
                            :href="(n.data.url as string)"
                            class="text-xs text-slate-500 hover:underline whitespace-nowrap"
                        >
                            View
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination v-if="notifications.last_page > 1" :paginator="notifications" />
        </div>
    </AppLayout>
</template>
