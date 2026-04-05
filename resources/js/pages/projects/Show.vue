<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type ActivityItem, type BreadcrumbItem, type Project, type ProjectCoverage, USER_ROLE_LABELS } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    project: Project;
    coverage: ProjectCoverage;
    activity: ActivityItem[];
    can: { edit: boolean; manageMembers: boolean; delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.project.name, href: route('projects.show', { project: props.project.id }) },
];

const statusClasses: Record<string, string> = {
    active:   'bg-emerald-100 text-emerald-700',
    on_hold:  'bg-amber-100 text-amber-700',
    archived: 'bg-slate-100 text-slate-500',
};

const roleColors: Record<string, string> = {
    admin:            'bg-red-100 text-red-700',
    project_manager:  'bg-violet-100 text-violet-700',
    business_analyst: 'bg-blue-100 text-blue-700',
    developer:        'bg-emerald-100 text-emerald-700',
    tester:           'bg-amber-100 text-amber-700',
    viewer:           'bg-slate-100 text-slate-500',
};

function confirmDelete() {
    if (confirm(`Delete "${props.project.name}"? This cannot be undone.`)) {
        router.delete(route('projects.destroy', { project: props.project.id }));
    }
}
</script>

<template>
    <Head :title="project.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 stagger">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="text-primary font-bold">
                            {{ project.name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-semibold text-slate-900">{{ project.name }}</h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClasses[project.status]">
                                {{ project.status_label }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">Owned by {{ project.owner }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link v-if="can.manageMembers" :href="route('projects.members.index', { project: project.id })"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-colors">
                        Members
                    </Link>
                    <Link v-if="can.edit" :href="route('projects.edit', { project: project.id })"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-colors">
                        Edit
                    </Link>
                    <button v-if="can.delete" @click="confirmDelete"
                        class="text-sm font-medium text-red-600 hover:text-red-700 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Description -->
            <div v-if="project.description" class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm text-slate-600">{{ project.description }}</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <p class="text-xs text-slate-500 mb-1">Members</p>
                    <p class="text-2xl font-bold text-slate-900">{{ project.members?.length ?? 0 }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <p class="text-xs text-slate-500 mb-1">Start Date</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ project.start_date ?? '—' }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <p class="text-xs text-slate-500 mb-1">Target Date</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ project.target_date ?? '—' }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <p class="text-xs text-slate-500 mb-1">Created</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ project.created_at }}</p>
                </div>
            </div>

            <!-- Coverage Overview + Recent Activity -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Coverage Overview -->
                <div class="col-span-2 bg-white border border-slate-200 rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-slate-800 text-sm">Coverage Overview</h2>
                        <Link :href="route('projects.rtm', { project: project.id })" class="text-xs text-primary font-medium hover:underline">
                            View RTM →
                        </Link>
                    </div>

                    <!-- Counts -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 rounded-lg px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ coverage.br_count }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Business Reqs</p>
                        </div>
                        <div class="bg-slate-50 rounded-lg px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ coverage.tr_count }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Technical Reqs</p>
                        </div>
                        <div class="bg-slate-50 rounded-lg px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ coverage.tc_count }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Test Cases</p>
                        </div>
                    </div>

                    <!-- Progress bars -->
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-slate-600">BR Coverage</span>
                                <span class="text-xs font-bold"
                                    :class="coverage.br_coverage >= 80 ? 'text-emerald-600' : coverage.br_coverage >= 50 ? 'text-amber-500' : 'text-red-500'">
                                    {{ coverage.br_coverage }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    :class="coverage.br_coverage >= 80 ? 'bg-emerald-500' : coverage.br_coverage >= 50 ? 'bg-amber-400' : 'bg-red-500'"
                                    :style="{ width: coverage.br_coverage + '%' }" />
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ coverage.covered_brs }} of {{ coverage.br_count }} covered</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-slate-600">TR Coverage</span>
                                <span class="text-xs font-bold"
                                    :class="coverage.tr_coverage >= 80 ? 'text-emerald-600' : coverage.tr_coverage >= 50 ? 'text-amber-500' : 'text-red-500'">
                                    {{ coverage.tr_coverage }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    :class="coverage.tr_coverage >= 80 ? 'bg-emerald-500' : coverage.tr_coverage >= 50 ? 'bg-amber-400' : 'bg-red-500'"
                                    :style="{ width: coverage.tr_coverage + '%' }" />
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ coverage.covered_trs }} of {{ coverage.tr_count }} covered</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                    <h2 class="font-semibold text-slate-800 text-sm">Recent Activity</h2>

                    <div v-if="activity.length === 0" class="text-sm text-slate-400 italic pt-2">
                        No activity yet.
                    </div>

                    <div v-for="item in activity" :key="item.id" class="flex gap-2.5 text-xs">
                        <!-- Subject type badge -->
                        <span class="mt-0.5 flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold"
                            :class="{
                                'bg-sky-100 text-sky-700':       item.subject_type === 'BR',
                                'bg-purple-100 text-purple-700': item.subject_type === 'TR',
                                'bg-amber-100 text-amber-700':   item.subject_type === 'TC',
                                'bg-slate-100 text-slate-600':   item.subject_type === 'Task',
                            }">
                            {{ item.subject_type }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-slate-700 leading-snug">
                                <span class="font-medium">{{ item.user_name }}</span>
                                <span class="text-slate-400">{{' '}}{{
                                    item.action === 'commented' ? 'commented on' :
                                    item.action === 'logged_hours' ? 'logged hours on' :
                                    item.action
                                }}{{' '}}</span>
                                <a :href="item.path" class="font-medium hover:text-primary hover:underline truncate">{{ item.subject_title ?? `#${item.subject_id}` }}</a>
                            </p>
                            <p class="text-slate-400 mt-0.5">{{ item.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Members -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-800 text-sm">Team Members</h2>
                    <Link v-if="can.manageMembers" :href="route('projects.members.index', { project: project.id })"
                        class="text-xs text-primary font-medium hover:underline">
                        Manage →
                    </Link>
                </div>
                <div class="space-y-2">
                    <div v-for="member in project.members" :key="member.id"
                        class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-xs">
                                {{ member.name.split(' ').map((w: string) => w[0]).slice(0,2).join('').toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ member.name }}</p>
                                <p class="text-xs text-slate-400">{{ member.email }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="roleColors[member.role]">
                            {{ USER_ROLE_LABELS[member.role] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
