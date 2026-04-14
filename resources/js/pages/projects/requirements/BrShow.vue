<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import { type BreadcrumbItem, type BusinessRequirement } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    project: { id: number; name: string };
    br: BusinessRequirement;
    linkable_trs: { id: number; ref: string; title: string }[];
    linkable_brs: { id: number; ref: string; title: string }[];
    can: { edit: boolean; delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
    { title: props.br.ref, href: '#' },
];


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

const depForm = useForm({ blocking_br_id: '' });
const depSearch = ref('');

const filteredLinkableBrs = computed(() => {
    const q = depSearch.value.toLowerCase();
    return props.linkable_brs.filter(b =>
        !q || b.title.toLowerCase().includes(q) || b.ref.toLowerCase().includes(q)
    );
});

function addBlocker() {
    depForm.post(route('projects.requirements.business.dependencies.store', [props.project.id, props.br.id]), {
        onSuccess: () => { depForm.reset(); depSearch.value = ''; },
    });
}

function removeBlocker(blockingBrId: number) {
    router.delete(route('projects.requirements.business.dependencies.destroy', [props.project.id, props.br.id, blockingBrId]));
}

function removeDependant(dependantBrId: number) {
    // Remove this BR as a blocker of dependantBrId — delete from the dependant's perspective
    router.delete(route('projects.requirements.business.dependencies.destroy', [props.project.id, dependantBrId, props.br.id]));
}
</script>

<template>
    <Head :title="br.ref" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-4xl stagger">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-mono text-sm text-slate-400">{{ br.ref }}</span>
                        <PriorityBadge :priority="br.priority" :label="br.priority_label" />
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="statusClass[br.status]">
                            {{ br.status_label }}
                        </span>
                        <span v-if="br.is_blocked"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                            ⚠ Blocked
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

            <!-- PERT Estimate panel -->
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-700">Effort Estimate (PERT)</h2>
                    <Link v-if="can.edit" :href="route('projects.requirements.business.edit', [project.id, br.id])"
                        class="text-xs text-slate-400 hover:text-primary transition">Edit →</Link>
                </div>
                <div v-if="br.has_pert" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-1">Optimistic</p>
                        <p class="text-lg font-semibold text-slate-700">{{ Number(br.optimistic_hours).toFixed(1) }} h</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-1">Most Likely</p>
                        <p class="text-lg font-semibold text-slate-700">{{ Number(br.most_likely_hours).toFixed(1) }} h</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-1">Pessimistic</p>
                        <p class="text-lg font-semibold text-slate-700">{{ Number(br.pessimistic_hours).toFixed(1) }} h</p>
                    </div>
                    <div class="text-center border-l border-slate-100 pl-4">
                        <p class="text-xs text-slate-400 mb-1">Expected (PERT)</p>
                        <p class="text-lg font-semibold text-primary">{{ Number(br.pert_expected).toFixed(1) }} h</p>
                        <p class="text-xs text-slate-400">±{{ Number(br.pert_std_dev).toFixed(1) }} h σ</p>
                    </div>
                </div>
                <div v-else class="flex items-center gap-2 text-sm text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    No PERT estimate yet. Add optimistic, most likely, and pessimistic hours to enable sprint capacity planning.
                </div>
            </div>

            <!-- Description -->
            <div v-if="br.description" class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-medium text-slate-500 mb-2">Description</h2>
                <div class="prose prose-sm max-w-none text-slate-700" v-html="br.description" />
            </div>

            <!-- Dependencies -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Dependencies</h2>
                    <Link :href="route('projects.requirements.business.graph', project.id)"
                        class="text-xs text-slate-400 hover:text-primary transition">
                        View graph →
                    </Link>
                </div>

                <div class="divide-y divide-slate-100">
                    <!-- Blocked By (incoming) -->
                    <div class="px-5 py-3">
                        <p class="text-xs font-medium text-slate-500 mb-2">Blocked By
                            <span class="text-slate-400 font-normal">(must be resolved first)</span>
                        </p>

                        <!-- Add blocker form -->
                        <div v-if="can.edit && linkable_brs.length" class="flex gap-2 mb-3">
                            <div class="flex-1">
                                <input v-model="depSearch" type="text" placeholder="Search BRs…"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>
                            <div class="flex-1">
                                <select v-model="depForm.blocking_br_id"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                                    <option value="">— Select blocker —</option>
                                    <option v-for="b in filteredLinkableBrs" :key="b.id" :value="b.id">
                                        {{ b.ref }} — {{ b.title }}
                                    </option>
                                </select>
                            </div>
                            <button type="button" :disabled="!depForm.blocking_br_id || depForm.processing"
                                @click="addBlocker"
                                class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-40 flex-shrink-0">
                                Add
                            </button>
                        </div>
                        <p v-if="depForm.errors.blocking_br_id" class="text-xs text-red-500 mb-2">{{ depForm.errors.blocking_br_id }}</p>

                        <div v-if="br.blocked_by.length === 0" class="text-xs text-slate-400 py-1">None — this BR has no prerequisites.</div>
                        <div v-else class="space-y-1">
                            <div v-for="dep in br.blocked_by" :key="dep.id"
                                class="flex items-center gap-3 py-1">
                                <Link :href="route('projects.requirements.business.show', [project.id, dep.id])"
                                    class="font-mono text-xs text-slate-400 hover:text-primary transition w-16 flex-shrink-0">
                                    {{ dep.ref }}
                                </Link>
                                <span class="text-sm text-slate-700 flex-1 truncate">{{ dep.title }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                    :class="statusClass[dep.status]">
                                    {{ dep.status_label }}
                                </span>
                                <button v-if="can.edit" @click="removeBlocker(dep.id)"
                                    class="text-xs text-red-400 hover:text-red-600 flex-shrink-0">Remove</button>
                            </div>
                        </div>
                    </div>

                    <!-- Blocks (outgoing) -->
                    <div class="px-5 py-3">
                        <p class="text-xs font-medium text-slate-500 mb-2">Blocks
                            <span class="text-slate-400 font-normal">(depends on this BR)</span>
                        </p>
                        <div v-if="br.blocks.length === 0" class="text-xs text-slate-400 py-1">None — no BRs depend on this one.</div>
                        <div v-else class="space-y-1">
                            <div v-for="dep in br.blocks" :key="dep.id"
                                class="flex items-center gap-3 py-1">
                                <Link :href="route('projects.requirements.business.show', [project.id, dep.id])"
                                    class="font-mono text-xs text-slate-400 hover:text-primary transition w-16 flex-shrink-0">
                                    {{ dep.ref }}
                                </Link>
                                <span class="text-sm text-slate-700 flex-1 truncate">{{ dep.title }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                    :class="statusClass[dep.status]">
                                    {{ dep.status_label }}
                                </span>
                                <button v-if="can.edit" @click="removeDependant(dep.id)"
                                    class="text-xs text-red-400 hover:text-red-600 flex-shrink-0">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
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
