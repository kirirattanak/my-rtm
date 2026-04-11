<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface GraphNode {
    id: number;
    ref: string;
    title: string;
    status: string;
    priority: string;
}

interface GraphEdge {
    from: number;
    to: number;
}

interface PositionedNode extends GraphNode {
    x: number;
    y: number;
    layer: number;
    is_blocked: boolean;
    is_ready: boolean; // no unresolved incoming blockers
}

const props = defineProps<{
    project: { id: number; name: string };
    nodes: GraphNode[];
    edges: GraphEdge[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: route('projects.show', props.project.id) },
    { title: 'Business Requirements', href: route('projects.requirements.business.index', props.project.id) },
    { title: 'Dependency Graph', href: '#' },
];

// ── Layout constants ────────────────────────────────────────────────
const NODE_W    = 210;
const NODE_H    = 68;
const LAYER_GAP = 280;
const NODE_GAP  = 90;
const PAD       = 40;

// ── Status colour maps ───────────────────────────────────────────────
const statusFill: Record<string, string> = {
    draft:       '#f1f5f9',
    review:      '#dbeafe',
    approved:    '#d1fae5',
    implemented: '#ede9fe',
    deprecated:  '#fee2e2',
};
const statusStroke: Record<string, string> = {
    draft:       '#94a3b8',
    review:      '#3b82f6',
    approved:    '#10b981',
    implemented: '#8b5cf6',
    deprecated:  '#ef4444',
};
const statusText: Record<string, string> = {
    draft:       '#64748b',
    review:      '#1d4ed8',
    approved:    '#065f46',
    implemented: '#5b21b6',
    deprecated:  '#b91c1c',
};

// ── DAG layout ──────────────────────────────────────────────────────
const { positioned, isolated, svgWidth, svgHeight } = computed(() => {
    const nodeMap = new Map(props.nodes.map(n => [n.id, n]));
    const outEdges: Record<number, number[]> = {};
    const inEdges:  Record<number, number[]> = {};

    for (const n of props.nodes) {
        outEdges[n.id] = [];
        inEdges[n.id]  = [];
    }
    for (const e of props.edges) {
        outEdges[e.from]?.push(e.to);
        inEdges[e.to]?.push(e.from);
    }

    // Nodes with no edges at all — show separately
    const isolatedNodes = props.nodes.filter(
        n => outEdges[n.id].length === 0 && inEdges[n.id].length === 0
    );
    const connectedIds = new Set(
        props.nodes.filter(n => outEdges[n.id].length > 0 || inEdges[n.id].length > 0).map(n => n.id)
    );

    // Assign layers via longest-path from sources
    const layer: Record<number, number> = {};
    for (const n of props.nodes) layer[n.id] = 0;

    // BFS in topological order
    const tempIn: Record<number, number> = {};
    for (const n of props.nodes) tempIn[n.id] = inEdges[n.id].length;
    const queue = props.nodes.filter(n => tempIn[n.id] === 0).map(n => n.id);
    const visited = new Set<number>();

    while (queue.length > 0) {
        const curr = queue.shift()!;
        if (visited.has(curr)) continue;
        visited.add(curr);
        for (const next of outEdges[curr]) {
            layer[next] = Math.max(layer[next], layer[curr] + 1);
            tempIn[next]--;
            if (tempIn[next] === 0) queue.push(next);
        }
    }

    // Group connected nodes by layer
    const layerGroups: Record<number, number[]> = {};
    for (const id of connectedIds) {
        const l = layer[id];
        if (!layerGroups[l]) layerGroups[l] = [];
        layerGroups[l].push(id);
    }

    // Compute positions
    const positions: Record<number, { x: number; y: number }> = {};
    for (const [l, ids] of Object.entries(layerGroups)) {
        ids.forEach((id, i) => {
            positions[id] = {
                x: Number(l) * LAYER_GAP + PAD,
                y: i * NODE_GAP + PAD,
            };
        });
    }

    // is_blocked / is_ready per node
    const isBlocked = (id: number) =>
        inEdges[id].some(bid => nodeMap.get(bid)?.status !== 'implemented');
    const isReady = (id: number) =>
        inEdges[id].length > 0 && !isBlocked(id);

    const positioned: PositionedNode[] = props.nodes
        .filter(n => connectedIds.has(n.id))
        .map(n => ({
            ...n,
            x: positions[n.id]?.x ?? PAD,
            y: positions[n.id]?.y ?? PAD,
            layer: layer[n.id],
            is_blocked: isBlocked(n.id),
            is_ready: isReady(n.id),
        }));

    const isolated: PositionedNode[] = isolatedNodes.map((n, i) => ({
        ...n,
        x: PAD,
        y: i * NODE_GAP + PAD,
        layer: 0,
        is_blocked: false,
        is_ready: false,
    }));

    const maxLayer  = positioned.length > 0 ? Math.max(...positioned.map(n => n.layer)) : 0;
    const maxPerLayer = Object.values(layerGroups).reduce((m, ids) => Math.max(m, ids.length), 0);
    const svgWidth  = (maxLayer + 1) * LAYER_GAP + PAD * 2;
    const svgHeight = Math.max(maxPerLayer * NODE_GAP + PAD * 2, NODE_H + PAD * 2);

    return { positioned, isolated, svgWidth, svgHeight };
}).value;

// ── Edge path helper ────────────────────────────────────────────────
function edgePath(e: GraphEdge, posMap: Map<number, PositionedNode>): string {
    const src = posMap.get(e.from);
    const tgt = posMap.get(e.to);
    if (!src || !tgt) return '';

    const x1 = src.x + NODE_W;
    const y1 = src.y + NODE_H / 2;
    const x2 = tgt.x;
    const y2 = tgt.y + NODE_H / 2;
    const cx = (x1 + x2) / 2;

    return `M ${x1} ${y1} C ${cx} ${y1}, ${cx} ${y2}, ${x2} ${y2}`;
}

function edgeColor(e: GraphEdge, posMap: Map<number, PositionedNode>): string {
    const src = posMap.get(e.from);
    return src?.status === 'implemented' ? '#10b981' : '#f59e0b';
}

const posMap = computed(() => new Map(positioned.map(n => [n.id, n])));

// ── Tooltip ─────────────────────────────────────────────────────────
const tooltip = ref<{ node: PositionedNode; x: number; y: number } | null>(null);

function showTooltip(node: PositionedNode, evt: MouseEvent) {
    tooltip.value = { node, x: evt.clientX + 12, y: evt.clientY + 12 };
}
function hideTooltip() {
    tooltip.value = null;
}

// ── Truncate helper ─────────────────────────────────────────────────
function truncate(str: string, max = 26): string {
    return str.length > max ? str.slice(0, max) + '…' : str;
}
</script>

<template>
    <Head title="Dependency Graph" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">BR Dependency Graph</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ nodes.length }} business requirement{{ nodes.length !== 1 ? 's' : '' }} · {{ edges.length }} link{{ edges.length !== 1 ? 's' : '' }}</p>
                </div>
                <Link :href="route('projects.requirements.business.index', project.id)"
                    class="text-sm text-slate-500 hover:text-slate-700">
                    ← Back to list
                </Link>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500 bg-white border border-slate-200 rounded-xl px-4 py-3">
                <span class="font-medium text-slate-600">Status:</span>
                <span v-for="(fill, key) in statusFill" :key="key" class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm border" :style="`background:${fill};border-color:${statusStroke[key]}`" />
                    {{ key.charAt(0).toUpperCase() + key.slice(1) }}
                </span>
                <span class="ml-4 font-medium text-slate-600">Edge:</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-0.5 rounded bg-emerald-500 inline-block"/> Resolved</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-0.5 rounded bg-amber-400 inline-block"/> Pending</span>
                <span class="ml-4 font-medium text-slate-600">Node:</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm border-2 border-amber-400 inline-block"/> Blocked</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm border-2 border-emerald-500 inline-block"/> Ready</span>
            </div>

            <!-- Empty state -->
            <div v-if="nodes.length === 0" class="text-center py-20 text-slate-400">
                <p class="text-lg font-medium">No business requirements yet</p>
            </div>

            <template v-else>
                <!-- Connected graph -->
                <div v-if="positioned.length > 0" class="bg-white rounded-xl border border-slate-200 overflow-auto">
                    <svg
                        :width="svgWidth"
                        :height="svgHeight"
                        class="block"
                    >
                        <defs>
                            <marker id="arrow-pending" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                                <path d="M0,0 L0,6 L8,3 z" fill="#f59e0b" />
                            </marker>
                            <marker id="arrow-resolved" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                                <path d="M0,0 L0,6 L8,3 z" fill="#10b981" />
                            </marker>
                        </defs>

                        <!-- Edges -->
                        <g v-for="edge in edges" :key="`${edge.from}-${edge.to}`">
                            <path
                                :d="edgePath(edge, posMap)"
                                :stroke="edgeColor(edge, posMap)"
                                stroke-width="2"
                                fill="none"
                                :marker-end="edgeColor(edge, posMap) === '#10b981' ? 'url(#arrow-resolved)' : 'url(#arrow-pending)'"
                            />
                        </g>

                        <!-- Nodes -->
                        <g
                            v-for="node in positioned"
                            :key="node.id"
                            :transform="`translate(${node.x}, ${node.y})`"
                            class="cursor-pointer"
                            @mouseenter="showTooltip(node, $event)"
                            @mousemove="showTooltip(node, $event)"
                            @mouseleave="hideTooltip"
                            @click="$inertia.visit(route('projects.requirements.business.show', [project.id, node.id]))"
                        >
                            <!-- Node background -->
                            <rect
                                :width="NODE_W"
                                :height="NODE_H"
                                rx="8"
                                :fill="statusFill[node.status] ?? '#f1f5f9'"
                                :stroke="node.is_blocked ? '#f59e0b' : node.is_ready ? '#10b981' : (statusStroke[node.status] ?? '#94a3b8')"
                                :stroke-width="node.is_blocked || node.is_ready ? 2.5 : 1.5"
                            />
                            <!-- Ref -->
                            <text
                                x="12" y="20"
                                font-size="10"
                                font-family="monospace"
                                :fill="statusText[node.status] ?? '#64748b'"
                                opacity="0.8"
                            >{{ node.ref }}</text>
                            <!-- Title -->
                            <text
                                x="12" y="38"
                                font-size="12"
                                font-weight="600"
                                :fill="statusText[node.status] ?? '#1e293b'"
                            >{{ truncate(node.title) }}</text>
                            <!-- Status label -->
                            <text
                                x="12" y="56"
                                font-size="10"
                                :fill="statusText[node.status] ?? '#64748b'"
                                opacity="0.7"
                            >{{ node.status.charAt(0).toUpperCase() + node.status.slice(1) }}{{ node.is_blocked ? ' · ⚠ Blocked' : node.is_ready ? ' · ✓ Ready' : '' }}</text>
                        </g>
                    </svg>
                </div>

                <!-- Isolated BRs (no links) -->
                <div v-if="isolated.length > 0" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-600">Unlinked BRs
                            <span class="text-slate-400 font-normal ml-1">— no dependencies defined</span>
                        </h2>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="node in isolated" :key="node.id"
                                class="hover:bg-slate-50 cursor-pointer transition"
                                @click="$inertia.visit(route('projects.requirements.business.show', [project.id, node.id]))">
                                <td class="px-5 py-2.5 font-mono text-xs text-slate-400 w-20">{{ node.ref }}</td>
                                <td class="px-5 py-2.5 text-slate-800">{{ node.title }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :style="`background:${statusFill[node.status]};color:${statusText[node.status]}`">
                                        {{ node.status.charAt(0).toUpperCase() + node.status.slice(1) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        <!-- Tooltip -->
        <Teleport to="body">
            <div v-if="tooltip"
                class="fixed z-50 pointer-events-none bg-slate-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl max-w-xs"
                :style="`left:${tooltip.x}px;top:${tooltip.y}px`">
                <p class="font-mono opacity-70 mb-0.5">{{ tooltip.node.ref }}</p>
                <p class="font-semibold">{{ tooltip.node.title }}</p>
                <p class="opacity-70 mt-1 capitalize">{{ tooltip.node.status }} · {{ tooltip.node.priority }} priority</p>
                <p v-if="tooltip.node.is_blocked" class="text-amber-400 mt-0.5">⚠ Blocked by unresolved prerequisites</p>
                <p v-else-if="tooltip.node.is_ready" class="text-emerald-400 mt-0.5">✓ All prerequisites resolved</p>
            </div>
        </Teleport>
    </AppLayout>
</template>
