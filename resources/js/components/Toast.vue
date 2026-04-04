<script setup lang="ts">
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface ToastItem {
    id: number;
    message: string;
    type: 'success' | 'error';
}

const page = usePage<SharedData>();
const toasts = ref<ToastItem[]>([]);
let nextId = 0;

function addToast(message: string, type: 'success' | 'error') {
    const id = ++nextId;
    toasts.value.push({ id, message, type });
    setTimeout(() => dismiss(id), 4000);
}

function dismiss(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

const flash = computed(() => page.props.flash);

watch(flash, (val) => {
    if (val?.success) addToast(val.success, 'success');
    if (val?.error)   addToast(val.error, 'error');
}, { deep: true });
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
            <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium min-w-64 max-w-sm"
                    :class="toast.type === 'success'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-red-600 text-white'"
                >
                    <span v-if="toast.type === 'success'" class="text-base leading-none">✓</span>
                    <span v-else class="text-base leading-none">✕</span>
                    <span class="flex-1">{{ toast.message }}</span>
                    <button
                        @click="dismiss(toast.id)"
                        class="opacity-70 hover:opacity-100 transition-opacity leading-none"
                    >
                        ✕
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: all 0.2s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
