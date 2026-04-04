<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Project, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{ projects: Project[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const user = usePage<SharedData>().props.auth.user;
const canCreate = ['admin', 'project_manager'].includes(user.role);

const statusClasses: Record<string, string> = {
    active:   'bg-emerald-100 text-emerald-700',
    on_hold:  'bg-amber-100 text-amber-700',
    archived: 'bg-slate-100 text-slate-500',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">All Projects</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }}</p>
                </div>
                <Link v-if="canCreate" :href="route('projects.create')"
                    class="inline-flex items-center gap-2 bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                    + New Project
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="projects.length === 0"
                class="bg-white border border-dashed border-slate-200 rounded-xl p-12 text-center">
                <p class="text-slate-500 text-sm">No projects yet.</p>
                <Link v-if="canCreate" :href="route('projects.create')"
                    class="mt-3 inline-block text-sm text-primary font-medium hover:underline">
                    Create your first project →
                </Link>
            </div>

            <!-- Project cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <Link v-for="project in projects" :key="project.id"
                    :href="route('projects.show', project.id)"
                    class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-shadow block">

                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-primary font-bold text-sm">
                                    {{ project.name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 text-sm">{{ project.name }}</h3>
                                <p class="text-xs text-slate-400">{{ project.owner }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClasses[project.status]">
                            {{ project.status_label }}
                        </span>
                    </div>

                    <p v-if="project.description"
                        class="text-xs text-slate-500 mb-4 line-clamp-2">{{ project.description }}</p>
                    <p v-else class="text-xs text-slate-300 mb-4 italic">No description</p>

                    <div class="flex items-center justify-between text-xs text-slate-400 mt-auto pt-2 border-t border-slate-100">
                        <span>{{ project.members_count }} member{{ project.members_count !== 1 ? 's' : '' }}</span>
                        <span v-if="project.target_date">Due {{ project.target_date }}</span>
                    </div>
                </Link>

                <!-- New project card -->
                <Link v-if="canCreate" :href="route('projects.create')"
                    class="border-2 border-dashed border-slate-200 rounded-xl p-5 flex flex-col items-center justify-center gap-2 hover:border-primary/40 hover:bg-primary/5 transition-colors group">
                    <div class="w-10 h-10 rounded-full bg-slate-100 group-hover:bg-primary/10 flex items-center justify-center transition-colors">
                        <span class="text-slate-400 group-hover:text-primary text-xl leading-none">+</span>
                    </div>
                    <p class="text-sm text-slate-400 group-hover:text-primary font-medium">New Project</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
