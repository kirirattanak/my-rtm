<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type BusinessRequirement } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    project: { id: number; name: string };
    br: BusinessRequirement;
    linkable_trs: { id: number; ref: string; title: string }[];
    can: { edit: boolean; delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
    { title: props.br.ref, href: '#' },
];

const priorityClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-slate-100 text-slate-500',
};

const statusClass: Record<string, string> = {
    draft:       'bg-slate-100 text-slate-500',
    review:      'bg-blue-100 text-blue-700',
    approved:    'bg-emerald-100 text-emerald-700',
    implemented: 'bg-violet-100 text-violet-700',
    deprecated:  'bg-red-100 text-red-500',
};

const trStatusClass: Record<string, string> = statusClass;

const linkForm = useForm({ technical_requirement_id: '' });

function linkTr() {
    linkForm.post(route('projects.requirements.business.tr-links.store', [props.project.id, props.br.id]), {
        onSuccess: () => linkForm.reset(),
    });
}

const commentForm = useForm({ body: '' });

function submitComment() {
    commentForm.post(route('projects.requirements.business.comments.store', [props.project.id, props.br.id]), {
        onSuccess: () => commentForm.reset(),
    });
}

function deleteComment(commentId: number) {
    if (confirm('Delete this comment?')) {
        router.delete(route('projects.requirements.comments.destroy', [props.project.id, commentId]));
    }
}

function confirmDelete() {
    if (confirm(`Delete "${props.br.ref}"? This cannot be undone.`)) {
        router.delete(route('projects.requirements.business.destroy', [props.project.id, props.br.id]));
    }
}

function unlinkTr(trId: number) {
    router.delete(route('projects.requirements.business.tr-links.destroy', [props.project.id, props.br.id, trId]));
}
</script>

<template>
    <Head :title="br.ref" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-4xl stagger">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm text-slate-400">{{ br.ref }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="priorityClass[br.priority]">
                            {{ br.priority_label }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClass[br.status]">
                            {{ br.status_label }}
                        </span>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ br.title }}</h1>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span v-if="br.category">Category: {{ br.category }}</span>
                        <span>Created by {{ br.creator?.name }}</span>
                        <span>{{ new Date(br.created_at).toLocaleDateString() }}</span>
                    </div>
                    <div v-if="br.tags.length" class="flex flex-wrap gap-1">
                        <span
                            v-for="tag in br.tags"
                            :key="tag"
                            class="px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full"
                        >{{ tag }}</span>
                    </div>
                </div>
                <div v-if="can.edit || can.delete" class="flex items-center gap-2 flex-shrink-0">
                    <Link
                        v-if="can.edit"
                        :href="route('projects.requirements.business.edit', [project.id, br.id])"
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
            <div v-if="br.description" class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-medium text-slate-500 mb-2">Description</h2>
                <div class="prose prose-sm max-w-none text-slate-700" v-html="br.description" />
            </div>

            <!-- Linked TRs -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">Linked Technical Requirements</h2>
                    <form v-if="can.edit && linkable_trs.length" @submit.prevent="linkTr" class="flex gap-2">
                        <select
                            v-model="linkForm.technical_requirement_id"
                            class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        >
                            <option value="" disabled>Select a TR to link…</option>
                            <option v-for="tr in linkable_trs" :key="tr.id" :value="tr.id">
                                {{ tr.ref }} — {{ tr.title }}
                            </option>
                        </select>
                        <button
                            type="submit"
                            :disabled="!linkForm.technical_requirement_id || linkForm.processing"
                            class="px-3 py-1.5 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition disabled:opacity-50"
                        >
                            Link
                        </button>
                    </form>
                    <p v-else-if="can.edit && !linkable_trs.length" class="text-xs text-slate-400">
                        All project TRs are already linked.
                    </p>
                </div>
                <div v-if="br.technical_requirements.length === 0" class="px-5 py-4 text-sm text-slate-400">
                    No technical requirements linked yet.
                </div>
                <table v-else class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tr in br.technical_requirements" :key="tr.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs text-slate-400 w-20">{{ tr.ref }}</td>
                            <td class="px-5 py-3 text-slate-800">
                                <Link :href="route('projects.requirements.technical.show', [project.id, tr.id])" class="hover:text-primary">
                                    {{ tr.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="trStatusClass[tr.status]">
                                    {{ tr.status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-400 text-xs">{{ tr.type_label }}</td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    v-if="can.edit"
                                    @click="unlinkTr(tr.id)"
                                    class="text-xs text-red-400 hover:text-red-600"
                                >
                                    Unlink
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Comments -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700">Comments</h2>

                <div v-if="br.comments.length === 0" class="text-sm text-slate-400">No comments yet.</div>

                <div v-for="comment in br.comments" :key="comment.id" class="flex gap-3">
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
