<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TechnicalRequirement } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    tr: TechnicalRequirement;
    can: { edit: boolean; delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Technical Requirements', href: route('projects.requirements.technical.index', props.project.id) },
    { title: props.tr.ref, href: '#' },
];

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

const typeClass: Record<string, string> = {
    functional:     'bg-sky-100 text-sky-700',
    non_functional: 'bg-purple-100 text-purple-700',
    constraint:     'bg-rose-100 text-rose-700',
};

const priorityClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-slate-100 text-slate-500',
};

const commentForm = useForm({ body: '' });

function submitComment() {
    commentForm.post(route('projects.requirements.technical.comments.store', [props.project.id, props.tr.id]), {
        onSuccess: () => commentForm.reset(),
    });
}

function deleteComment(commentId: number) {
    if (confirm('Delete this comment?')) {
        router.delete(route('projects.requirements.comments.destroy', [props.project.id, commentId]));
    }
}

function confirmDelete() {
    if (confirm(`Delete "${props.tr.ref}"? This cannot be undone.`)) {
        router.delete(route('projects.requirements.technical.destroy', [props.project.id, props.tr.id]));
    }
}
</script>

<template>
    <Head :title="tr.ref" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-4xl">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm text-slate-400">{{ tr.ref }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="typeClass[tr.type]">
                            {{ tr.type_label }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClass[tr.status]">
                            {{ tr.status_label }}
                        </span>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ tr.title }}</h1>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span>Created by {{ tr.creator?.name }}</span>
                        <span>{{ new Date(tr.created_at).toLocaleDateString() }}</span>
                    </div>
                </div>
                <div v-if="can.edit || can.delete" class="flex items-center gap-2 flex-shrink-0">
                    <Link
                        v-if="can.edit"
                        :href="route('projects.requirements.technical.edit', [project.id, tr.id])"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="can.delete"
                        @click="confirmDelete"
                        class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <!-- Description -->
            <div v-if="tr.description" class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-medium text-slate-500 mb-2">Description</h2>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ tr.description }}</p>
            </div>

            <!-- Linked BRs -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Traced from Business Requirements</h2>
                </div>
                <div v-if="tr.business_requirements.length === 0" class="px-5 py-4 text-sm text-slate-400">
                    No business requirements linked. Link this TR from a BR's detail page.
                </div>
                <table v-else class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="br in tr.business_requirements" :key="br.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs text-slate-400 w-20">{{ br.ref }}</td>
                            <td class="px-5 py-3 text-slate-800">
                                <Link :href="route('projects.requirements.business.show', [project.id, br.id])" class="hover:text-primary">
                                    {{ br.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="priorityClass[br.priority]">
                                    {{ br.priority_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass[br.status]">
                                    {{ br.status_label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Comments -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700">Comments</h2>

                <div v-if="tr.comments.length === 0" class="text-sm text-slate-400">No comments yet.</div>

                <div v-for="comment in tr.comments" :key="comment.id" class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary">
                        {{ comment.user.name[0].toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-xs font-medium text-slate-700">{{ comment.user.name }}</span>
                            <span class="text-xs text-slate-400">{{ new Date(comment.created_at).toLocaleDateString() }}</span>
                        </div>
                        <p class="text-sm text-slate-600">{{ comment.body }}</p>
                    </div>
                    <button @click="deleteComment(comment.id)" class="text-slate-300 hover:text-red-400 text-xs self-start">✕</button>
                </div>

                <!-- Comment form -->
                <form @submit.prevent="submitComment" class="flex gap-2 pt-2">
                    <textarea
                        v-model="commentForm.body"
                        rows="2"
                        placeholder="Add a comment..."
                        class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"
                    />
                    <button
                        type="submit"
                        :disabled="commentForm.processing || !commentForm.body.trim()"
                        class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50 self-end"
                    >
                        Post
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
