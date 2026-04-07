<script setup lang="ts">
import { ref } from 'vue';

interface Column {
    name: string;
    required?: boolean;
    values?: string;
    example: string;
}

interface SkippedRow {
    row: number;
    reason: string;
}

interface ImportResult {
    imported: number;
    skipped: SkippedRow[];
}

const props = defineProps<{
    columns: Column[];
    templateFilename: string;
    result: ImportResult | null;
    processing: boolean;
    error?: string;
}>();

const emit = defineEmits<{ (e: 'submit', file: File): void }>();

const file = ref<File | null>(null);
const dragOver = ref(false);

function onFileChange(e: Event) {
    file.value = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function onDrop(e: DragEvent) {
    dragOver.value = false;
    const dropped = e.dataTransfer?.files[0];
    if (dropped && (dropped.name.endsWith('.csv') || dropped.type === 'text/plain')) {
        file.value = dropped;
    }
}

function submit() {
    if (file.value) emit('submit', file.value);
}

function downloadTemplate() {
    const header = props.columns.map(c => c.name).join(',');
    const example = props.columns.map(c => `"${c.example}"`).join(',');
    const blob = new Blob([header + '\n' + example + '\n'], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = props.templateFilename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Template + column info -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">CSV Format</h2>
                    <p class="text-xs text-slate-500 mt-0.5">First row must be the header. Columns marked <span class="text-red-500 font-medium">*</span> are required.</p>
                </div>
                <button type="button" @click="downloadTemplate"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download template
                </button>
            </div>

            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 rounded">
                        <th class="px-3 py-2 text-left font-medium text-slate-500 rounded-l">Column</th>
                        <th class="px-3 py-2 text-left font-medium text-slate-500">Accepted values</th>
                        <th class="px-3 py-2 text-left font-medium text-slate-500 rounded-r">Example</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="col in columns" :key="col.name">
                        <td class="px-3 py-2 font-mono text-slate-700">
                            {{ col.name }}<span v-if="col.required" class="text-red-500 ml-0.5">*</span>
                        </td>
                        <td class="px-3 py-2 text-slate-500">{{ col.values ?? 'Any text' }}</td>
                        <td class="px-3 py-2 text-slate-400 italic">{{ col.example }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- File upload -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-semibold text-slate-800">Upload CSV</h2>

            <div @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="onDrop"
                :class="dragOver ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                @click="($refs.fileInput as HTMLInputElement).click()">
                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p v-if="file" class="text-sm font-medium text-slate-700">{{ file.name }}</p>
                <p v-else class="text-sm text-slate-400">Drop a CSV file here, or <span class="text-primary font-medium">browse</span></p>
                <p class="text-xs text-slate-300 mt-1">Max 5 MB · .csv files only</p>
                <input ref="fileInput" type="file" accept=".csv,text/plain" class="hidden" @change="onFileChange" />
            </div>

            <p v-if="error" class="text-xs text-red-500">{{ error }}</p>

            <button type="button" :disabled="!file || processing" @click="submit"
                class="w-full py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-40">
                {{ processing ? 'Importing…' : 'Import' }}
            </button>
        </div>

        <!-- Results -->
        <div v-if="result" class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium"
                    :class="result.imported > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ result.imported }} row{{ result.imported !== 1 ? 's' : '' }} imported
                </div>
                <div v-if="result.skipped.length > 0"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-50 text-amber-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ result.skipped.length }} row{{ result.skipped.length !== 1 ? 's' : '' }} skipped
                </div>
            </div>

            <div v-if="result.skipped.length > 0">
                <p class="text-xs font-medium text-slate-500 mb-2">Skipped rows</p>
                <table class="w-full text-xs border border-slate-100 rounded-lg overflow-hidden">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-slate-500 w-16">Row</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-500">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in result.skipped" :key="s.row">
                            <td class="px-3 py-2 text-slate-500 font-mono">{{ s.row }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ s.reason }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
