<script setup lang="ts">
import { type RtmBr } from '@/types';
import { VueFlow, useVueFlow, type Node, type Edge, Position } from '@vue-flow/core';
import { computed, ref } from 'vue';

const props = defineProps<{
    matrix: RtmBr[];
    projectId: number;
}>();

// ── Build nodes and edges from matrix data ──────────────────────────────────

const X_BR   = 60;
const X_TR   = 380;
const X_TC   = 700;
const Y_GAP  = 90;

const runColors: Record<string, string> = {
    pass:    '#10b981',
    fail:    '#ef4444',
    blocked: '#f59e0b',
    skipped: '#94a3b8',
};

const statusBg: Record<string, string> = {
    draft:       '#f1f5f9',
    review:      '#dbeafe',
    approved:    '#d1fae5',
    implemented: '#ede9fe',
    deprecated:  '#fee2e2',
};

const { nodes, edges } = computed(() => {
    const nodes: Node[] = [];
    const edges: Edge[] = [];

    // Track Y positions per column
    let brY = 40;
    let trY = 40;
    let tcY = 40;

    // Track which TR/TC nodes have already been placed (they can appear in multiple BRs)
    const trPlaced = new Map<number, number>(); // id -> y
    const tcPlaced = new Map<number, number>(); // id -> y

    for (const br of props.matrix) {
        const brNodeId = `br-${br.id}`;
        nodes.push({
            id: brNodeId,
            type: 'br',
            position: { x: X_BR, y: brY },
            data: br,
            sourcePosition: Position.Right,
            targetPosition: Position.Left,
        });
        brY += Y_GAP;

        for (const tr of br.trs) {
            const trNodeId = `tr-${tr.id}`;

            if (!trPlaced.has(tr.id)) {
                nodes.push({
                    id: trNodeId,
                    type: 'tr',
                    position: { x: X_TR, y: trY },
                    data: tr,
                    sourcePosition: Position.Right,
                    targetPosition: Position.Left,
                });
                trPlaced.set(tr.id, trY);
                trY += Y_GAP;
            }

            edges.push({
                id: `${brNodeId}-${trNodeId}`,
                source: brNodeId,
                target: trNodeId,
                style: { stroke: tr.is_covered ? '#10b981' : '#fca5a5', strokeWidth: 1.5 },
            });

            for (const tc of tr.test_cases) {
                const tcNodeId = `tc-${tc.id}`;

                if (!tcPlaced.has(tc.id)) {
                    nodes.push({
                        id: tcNodeId,
                        type: 'tc',
                        position: { x: X_TC, y: tcY },
                        data: tc,
                        sourcePosition: Position.Right,
                        targetPosition: Position.Left,
                    });
                    tcPlaced.set(tc.id, tcY);
                    tcY += Y_GAP;
                }

                edges.push({
                    id: `${trNodeId}-${tcNodeId}`,
                    source: trNodeId,
                    target: tcNodeId,
                    style: {
                        stroke: tc.is_passing ? '#10b981' : '#fca5a5',
                        strokeWidth: 1.5,
                    },
                });
            }
        }
    }

    return { nodes, edges };
}).value;

const { fitView } = useVueFlow();
</script>

<template>
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden" style="height: 600px;">
        <VueFlow
            :nodes="nodes"
            :edges="edges"
            :default-zoom="0.85"
            :min-zoom="0.3"
            :max-zoom="2"
            fit-view-on-init
            class="bg-slate-50"
        >
            <!-- Legend -->
            <template #default>
                <div class="absolute top-3 right-3 z-10 bg-white border border-slate-200 rounded-lg p-3 text-xs space-y-1.5 shadow-sm">
                    <p class="font-semibold text-slate-600 mb-2">Legend</p>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-sky-100 border border-sky-300 inline-block"/><span class="text-slate-600">Business Req.</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-purple-100 border border-purple-300 inline-block"/><span class="text-slate-600">Technical Req.</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-amber-100 border border-amber-300 inline-block"/><span class="text-slate-600">Test Case</span></div>
                    <hr class="border-slate-100 my-1"/>
                    <div class="flex items-center gap-2"><span class="w-4 h-0.5 bg-emerald-400 inline-block"/><span class="text-slate-600">Covered</span></div>
                    <div class="flex items-center gap-2"><span class="w-4 h-0.5 bg-red-300 inline-block"/><span class="text-slate-600">Gap</span></div>
                </div>
            </template>

            <!-- BR node -->
            <template #node-br="{ data }">
                <div class="px-3 py-2 rounded-lg border text-xs max-w-[240px] shadow-sm bg-sky-50 border-sky-200">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <span class="font-mono text-sky-400 text-[10px]">{{ data.ref }}</span>
                        <span v-if="data.is_covered" class="text-emerald-500 text-[10px] font-bold">✓</span>
                        <span v-else class="text-red-400 text-[10px] font-bold">✗</span>
                    </div>
                    <p class="text-slate-700 font-medium leading-snug line-clamp-2">{{ data.title }}</p>
                </div>
            </template>

            <!-- TR node -->
            <template #node-tr="{ data }">
                <div class="px-3 py-2 rounded-lg border text-xs max-w-[240px] shadow-sm bg-purple-50 border-purple-200">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <span class="font-mono text-purple-400 text-[10px]">{{ data.ref }}</span>
                        <span v-if="data.is_covered" class="text-emerald-500 text-[10px] font-bold">✓</span>
                        <span v-else class="text-red-400 text-[10px] font-bold">✗</span>
                    </div>
                    <p class="text-slate-700 font-medium leading-snug line-clamp-2">{{ data.title }}</p>
                </div>
            </template>

            <!-- TC node -->
            <template #node-tc="{ data }">
                <div class="px-3 py-2 rounded-lg border text-xs max-w-[200px] shadow-sm bg-amber-50 border-amber-200">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <span class="font-mono text-amber-400 text-[10px]">{{ data.ref }}</span>
                        <span v-if="data.latest_run"
                            class="text-[10px] font-bold"
                            :style="{ color: data.is_passing ? '#10b981' : '#ef4444' }">
                            {{ data.latest_run }}
                        </span>
                        <span v-else class="text-slate-300 text-[10px]">–</span>
                    </div>
                    <p class="text-slate-700 font-medium leading-snug line-clamp-2">{{ data.title }}</p>
                </div>
            </template>
        </VueFlow>
    </div>
</template>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
</style>
