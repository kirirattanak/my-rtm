<script setup lang="ts">
import { ref, computed } from 'vue';
import type { SelectOption } from '@/types';

const props = defineProps<{
    linkable_brs: SelectOption[];
    linkable_trs: SelectOption[];
    linkable_tcs: SelectOption[];
}>();

const brIds = defineModel<number[]>('brIds', { default: () => [] });
const trIds = defineModel<number[]>('trIds', { default: () => [] });
const tcIds = defineModel<number[]>('tcIds', { default: () => [] });

type LinkType = 'br' | 'tr' | 'tc';

const addType = ref<LinkType>('br');
const addId   = ref('');
const search  = ref('');


function poolFor(type: LinkType): SelectOption[] {
    return type === 'br' ? props.linkable_brs
         : type === 'tr' ? props.linkable_trs
         : props.linkable_tcs;
}
function selectedIds(type: LinkType): number[] {
    return type === 'br' ? brIds.value
         : type === 'tr' ? trIds.value
         : tcIds.value;
}

const filteredOptions = computed(() => {
    const q = search.value.toLowerCase();
    const already = selectedIds(addType.value);
    return poolFor(addType.value)
        .filter(o => !already.includes(Number(o.value)))
        .filter(o => !q || o.label.toLowerCase().includes(q));
});

function addLink() {
    if (!addId.value) return;
    const id = Number(addId.value);
    if (addType.value === 'br') brIds.value = [...brIds.value, id];
    else if (addType.value === 'tr') trIds.value = [...trIds.value, id];
    else tcIds.value = [...tcIds.value, id];
    addId.value = '';
    search.value = '';
}

function removeLink(type: LinkType, id: number) {
    if (type === 'br') brIds.value = brIds.value.filter(x => x !== id);
    else if (type === 'tr') trIds.value = trIds.value.filter(x => x !== id);
    else tcIds.value = tcIds.value.filter(x => x !== id);
}

function labelFor(type: LinkType, id: number): string {
    return poolFor(type).find(o => Number(o.value) === id)?.label ?? String(id);
}

const linkedItems = computed(() => [
    ...brIds.value.map(id => ({ type: 'br' as LinkType, id, label: labelFor('br', id) })),
    ...trIds.value.map(id => ({ type: 'tr' as LinkType, id, label: labelFor('tr', id) })),
    ...tcIds.value.map(id => ({ type: 'tc' as LinkType, id, label: labelFor('tc', id) })),
]);

// Reset addId when type changes
function onTypeChange() {
    addId.value = '';
    search.value = '';
}
</script>

<template>
    <div class="space-y-3">
        <p class="text-xs font-medium text-slate-600">Linked Requirements</p>

        <!-- Linked pills -->
        <div v-if="linkedItems.length" class="flex flex-wrap gap-1.5">
            <span v-for="item in linkedItems" :key="`${item.type}-${item.id}`"
                class="inline-flex items-center gap-1 pl-1.5 pr-1 py-0.5 rounded-full text-xs font-medium border"
                :class="item.type === 'br' ? 'bg-sky-50 text-sky-700 border-sky-200'
                      : item.type === 'tr' ? 'bg-purple-50 text-purple-700 border-purple-200'
                      : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                <span class="font-bold uppercase">{{ item.type }}</span>
                <span class="max-w-[200px] truncate">{{ item.label }}</span>
                <button type="button" @click="removeLink(item.type, item.id)"
                    class="ml-0.5 rounded-full hover:bg-black/10 p-0.5 leading-none">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </div>
        <p v-else class="text-xs text-slate-400 italic">No linked requirements yet.</p>

        <!-- Add link row -->
        <div class="flex items-end gap-2 flex-wrap">
            <!-- Type selector -->
            <div class="w-44">
                <label class="block text-[10px] font-medium text-slate-500 mb-1">Type</label>
                <select v-model="addType" @change="onTypeChange"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="br">Business Requirement</option>
                    <option value="tr">Technical Requirement</option>
                    <option value="tc">Test Case</option>
                </select>
            </div>

            <!-- Search filter -->
            <div class="flex-1 min-w-[160px]">
                <label class="block text-[10px] font-medium text-slate-500 mb-1">Search</label>
                <input v-model="search" type="text" placeholder="Filter by title or ref…"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40" />
            </div>

            <!-- Item select -->
            <div class="flex-1 min-w-[160px]">
                <label class="block text-[10px] font-medium text-slate-500 mb-1">Item</label>
                <select v-model="addId"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option v-if="filteredOptions.length === 0" value="" disabled>
                        {{ poolFor(addType).length > 0 ? 'All linked or no match' : 'None available' }}
                    </option>
                    <option v-else value="">— Select —</option>
                    <option v-for="opt in filteredOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Add button -->
            <button type="button" :disabled="!addId" @click="addLink"
                class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-40 flex-shrink-0">
                Add
            </button>
        </div>
    </div>
</template>
