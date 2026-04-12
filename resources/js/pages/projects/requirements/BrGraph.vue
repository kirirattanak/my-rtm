<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
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
    is_ready: boolean;
    titleLines: string[];
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
const NODE_W     = 240;
const NODE_H     = 82;
const LAYER_GAP  = 300;
const NODE_GAP   = 106;
const PAD        = 40;
const LEGEND_H   = 72; // height reserved below the graph for the embedded legend

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
const statusLabel: Record<string, string> = {
    draft: 'Draft', review: 'Review', approved: 'Approved',
    implemented: 'Implemented', deprecated: 'Deprecated',
};

// ── Text wrapping ────────────────────────────────────────────────────
// Usable width inside node: NODE_W - 24px padding = 216px.
// At 12px bold font, avg char ~7.2px → ~30 chars per line.
const MAX_CHARS = 30;

function wrapText(str: string): string[] {
    if (str.length <= MAX_CHARS) return [str];
    const words  = str.split(' ');
    const lines: string[] = [];
    let   current = '';
    for (const word of words) {
        const candidate = current ? `${current} ${word}` : word;
        if (candidate.length <= MAX_CHARS) {
            current = candidate;
        } else {
            if (current) lines.push(current);
            // Long single word: hard-break
            current = word.length > MAX_CHARS ? word.slice(0, MAX_CHARS - 1) + '…' : word;
        }
    }
    if (current) lines.push(current);
    return lines.slice(0, 2); // cap at 2 lines; most titles fit
}

// ── DAG layout ──────────────────────────────────────────────────────
const { positioned, isolated, svgWidth, svgHeight, legendY } = computed(() => {
    const nodeMap = new Map(props.nodes.map(n => [n.id, n]));
    const outEdges: Record<number, number[]> = {};
    const inEdges:  Record<number, number[]> = {};

    for (const n of props.nodes) { outEdges[n.id] = []; inEdges[n.id] = []; }
    for (const e of props.edges) {
        outEdges[e.from]?.push(e.to);
        inEdges[e.to]?.push(e.from);
    }

    const isolatedNodes = props.nodes.filter(
        n => outEdges[n.id].length === 0 && inEdges[n.id].length === 0
    );
    const connectedIds = new Set(
        props.nodes.filter(n => outEdges[n.id].length > 0 || inEdges[n.id].length > 0).map(n => n.id)
    );

    // Longest-path layer assignment
    const layer: Record<number, number> = {};
    for (const n of props.nodes) layer[n.id] = 0;
    const tempIn: Record<number, number> = {};
    for (const n of props.nodes) tempIn[n.id] = inEdges[n.id].length;
    const queue   = props.nodes.filter(n => tempIn[n.id] === 0).map(n => n.id);
    const visited = new Set<number>();
    while (queue.length > 0) {
        const curr = queue.shift()!;
        if (visited.has(curr)) continue;
        visited.add(curr);
        for (const next of outEdges[curr]) {
            layer[next] = Math.max(layer[next], layer[curr] + 1);
            if (--tempIn[next] === 0) queue.push(next);
        }
    }

    const layerGroups: Record<number, number[]> = {};
    for (const id of connectedIds) {
        const l = layer[id];
        if (!layerGroups[l]) layerGroups[l] = [];
        layerGroups[l].push(id);
    }

    const positions: Record<number, { x: number; y: number }> = {};
    for (const [l, ids] of Object.entries(layerGroups)) {
        ids.forEach((id, i) => {
            positions[id] = { x: Number(l) * LAYER_GAP + PAD, y: i * NODE_GAP + PAD };
        });
    }

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
            is_ready:   isReady(n.id),
            titleLines: wrapText(n.title),
        }));

    const isolated: PositionedNode[] = isolatedNodes.map((n, i) => ({
        ...n,
        x: PAD, y: i * NODE_GAP + PAD, layer: 0,
        is_blocked: false, is_ready: false,
        titleLines: wrapText(n.title),
    }));

    const maxLayer    = positioned.length > 0 ? Math.max(...positioned.map(n => n.layer)) : 0;
    const maxPerLayer = Object.values(layerGroups).reduce((m, ids) => Math.max(m, ids.length), 0);

    const contentWidth  = (maxLayer + 1) * LAYER_GAP + PAD * 2;
    const contentHeight = Math.max(maxPerLayer * NODE_GAP + PAD * 2, NODE_H + PAD * 2);

    // SVG must be wide enough for the legend (5 status items + edge/node labels)
    const svgWidth  = Math.max(contentWidth, 620);
    const legendY   = contentHeight + 8;
    const svgHeight = legendY + LEGEND_H;

    return { positioned, isolated, svgWidth, svgHeight, legendY };
}).value;

// ── Edge helpers ─────────────────────────────────────────────────────
function edgePath(e: GraphEdge, posMap: Map<number, PositionedNode>): string {
    const src = posMap.get(e.from);
    const tgt = posMap.get(e.to);
    if (!src || !tgt) return '';
    const x1 = src.x + NODE_W, y1 = src.y + NODE_H / 2;
    const x2 = tgt.x,          y2 = tgt.y + NODE_H / 2;
    const cx = (x1 + x2) / 2;
    return `M ${x1} ${y1} C ${cx} ${y1}, ${cx} ${y2}, ${x2} ${y2}`;
}

function edgeColor(e: GraphEdge, posMap: Map<number, PositionedNode>): string {
    return posMap.get(e.from)?.status === 'implemented' ? '#10b981' : '#f59e0b';
}

const posMap = computed(() => new Map(positioned.map(n => [n.id, n])));

// ── Tooltip ──────────────────────────────────────────────────────────
const tooltip = ref<{ node: PositionedNode; x: number; y: number } | null>(null);
function showTooltip(node: PositionedNode, evt: MouseEvent) {
    tooltip.value = { node, x: evt.clientX + 12, y: evt.clientY + 12 };
}
function hideTooltip() { tooltip.value = null; }

// ── PNG export ───────────────────────────────────────────────────────
const svgRef    = ref<SVGSVGElement | null>(null);
const exporting = ref(false);

async function exportPng() {
    const svg = svgRef.value;
    if (!svg) return;
    exporting.value = true;
    try {
        const scale = 2;
        const svgClone = svg.cloneNode(true) as SVGSVGElement;
        svgClone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        svgClone.setAttribute('width',  String(svgWidth));
        svgClone.setAttribute('height', String(svgHeight));

        const svgStr  = new XMLSerializer().serializeToString(svgClone);
        const svgBlob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
        const svgUrl  = URL.createObjectURL(svgBlob);

        const img = new Image();
        img.src   = svgUrl;
        await new Promise<void>((resolve, reject) => { img.onload = () => resolve(); img.onerror = reject; });

        const canvas  = document.createElement('canvas');
        canvas.width  = svgWidth  * scale;
        canvas.height = svgHeight * scale;
        const ctx     = canvas.getContext('2d')!;
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.scale(scale, scale);
        ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(svgUrl);

        const link    = document.createElement('a');
        link.download = `br-dependency-graph-${props.project.name.toLowerCase().replace(/\s+/g, '-')}.png`;
        link.href     = canvas.toDataURL('image/png');
        link.click();
    } finally {
        exporting.value = false;
    }
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
                <div class="flex items-center gap-3">
                    <button
                        v-if="positioned.length > 0"
                        @click="exportPng"
                        :disabled="exporting"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ exporting ? 'Exporting…' : 'Export PNG' }}
                    </button>
                    <Link :href="route('projects.requirements.business.index', { project: project.id })"
                        class="text-sm text-slate-500 hover:text-slate-700">
                        ← Back to list
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="nodes.length === 0" class="text-center py-20 text-slate-400">
                <p class="text-lg font-medium">No business requirements yet</p>
            </div>

            <template v-else>
                <!-- Connected graph -->
                <div v-if="positioned.length > 0" class="bg-white rounded-xl border border-slate-200 overflow-auto">
                    <svg
                        ref="svgRef"
                        :width="svgWidth"
                        :height="svgHeight"
                        class="block"
                    >
                        <defs>
                            <marker id="arrow-pending"  markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                                <path d="M0,0 L0,6 L8,3 z" fill="#f59e0b" />
                            </marker>
                            <marker id="arrow-resolved" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                                <path d="M0,0 L0,6 L8,3 z" fill="#10b981" />
                            </marker>
                        </defs>

                        <!-- Background -->
                        <rect width="100%" height="100%" fill="#f8fafc" />

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
                            @click="router.visit(route('projects.requirements.business.show', [project.id, node.id]))"
                        >
                            <rect
                                :width="NODE_W" :height="NODE_H" rx="8"
                                :fill="statusFill[node.status] ?? '#f1f5f9'"
                                :stroke="node.is_blocked ? '#f59e0b' : node.is_ready ? '#10b981' : (statusStroke[node.status] ?? '#94a3b8')"
                                :stroke-width="node.is_blocked || node.is_ready ? 2.5 : 1.5"
                            />
                            <!-- Ref -->
                            <text x="12" y="18" font-size="10" font-family="monospace"
                                :fill="statusText[node.status] ?? '#64748b'" opacity="0.8">
                                {{ node.ref }}
                            </text>
                            <!-- Title — wrapped, no truncation -->
                            <text font-size="12" font-weight="600" :fill="statusText[node.status] ?? '#1e293b'">
                                <tspan
                                    v-for="(line, i) in node.titleLines"
                                    :key="i"
                                    x="12"
                                    :y="36 + i * 16"
                                >{{ line }}</tspan>
                            </text>
                            <!-- Status / state label -->
                            <text x="12" :y="NODE_H - 12" font-size="10"
                                :fill="statusText[node.status] ?? '#64748b'" opacity="0.7">
                                {{ statusLabel[node.status] ?? node.status }}{{ node.is_blocked ? ' · ⚠ Blocked' : node.is_ready ? ' · ✓ Ready' : '' }}
                            </text>
                        </g>

                        <!-- ── Embedded legend ── -->
                        <g :transform="`translate(0, ${legendY})`">
                            <!-- Separator -->
                            <line :x1="PAD" y1="0" :x2="svgWidth - PAD" y2="0" stroke="#e2e8f0" stroke-width="1" />

                            <!-- Row 1: Status colours -->
                            <text :x="PAD" y="22" font-size="11" font-weight="600" fill="#475569">Status:</text>
                            <g v-for="(key, i) in Object.keys(statusFill)" :key="key"
                                :transform="`translate(${PAD + 68 + i * 96}, 10)`">
                                <rect width="13" height="13" rx="3"
                                    :fill="statusFill[key]"
                                    :stroke="statusStroke[key]" stroke-width="1.5" />
                                <text x="18" y="11" font-size="11" fill="#475569">{{ statusLabel[key] }}</text>
                            </g>

                            <!-- Row 2: Edge + node border states -->
                            <text :x="PAD" y="50" font-size="11" font-weight="600" fill="#475569">Edge:</text>
                            <!-- Resolved -->
                            <line :x1="PAD + 48" y1="45" :x2="PAD + 68" y2="45" stroke="#10b981" stroke-width="2" />
                            <text :x="PAD + 72" y="50" font-size="11" fill="#475569">Resolved</text>
                            <!-- Pending -->
                            <line :x1="PAD + 140" y1="45" :x2="PAD + 160" y2="45" stroke="#f59e0b" stroke-width="2" />
                            <text :x="PAD + 164" y="50" font-size="11" fill="#475569">Pending</text>

                            <text :x="PAD + 230" y="50" font-size="11" font-weight="600" fill="#475569">Node:</text>
                            <!-- Blocked -->
                            <rect :x="PAD + 275" y="38" width="13" height="13" rx="3"
                                fill="#fffbeb" stroke="#f59e0b" stroke-width="2" />
                            <text :x="PAD + 292" y="50" font-size="11" fill="#475569">Blocked</text>
                            <!-- Ready -->
                            <rect :x="PAD + 348" y="38" width="13" height="13" rx="3"
                                fill="#f0fdf4" stroke="#10b981" stroke-width="2" />
                            <text :x="PAD + 365" y="50" font-size="11" fill="#475569">Ready</text>
                        </g>
                    </svg>
                </div>

                <!-- Isolated BRs -->
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
                                @click="router.visit(route('projects.requirements.business.show', [project.id, node.id]))">
                                <td class="px-5 py-2.5 font-mono text-xs text-slate-400 w-20">{{ node.ref }}</td>
                                <td class="px-5 py-2.5 text-slate-800">{{ node.title }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :style="`background:${statusFill[node.status]};color:${statusText[node.status]}`">
                                        {{ statusLabel[node.status] ?? node.status }}
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
