<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let counter = 0;

const typeConfig = {
    success: {
        icon: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`,
        bg: 'bg-white',
        border: 'border-l-4 border-green-500',
        iconBg: 'bg-green-100 text-green-600',
        title: 'Success',
    },
    error: {
        icon: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`,
        bg: 'bg-white',
        border: 'border-l-4 border-red-500',
        iconBg: 'bg-red-100 text-red-600',
        title: 'Error',
    },
    info: {
        icon: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        bg: 'bg-white',
        border: 'border-l-4 border-blue-500',
        iconBg: 'bg-blue-100 text-blue-600',
        title: 'Info',
    },
};

function addToast(type, message) {
    if (!message) return;
    const id = ++counter;
    const config = typeConfig[type] || typeConfig.info;
    toasts.value.push({ id, type, message, config, progress: 100 });

    // Auto remove after 4.5s
    const interval = setInterval(() => {
        const toast = toasts.value.find(t => t.id === id);
        if (toast) {
            toast.progress -= 2.2;
            if (toast.progress <= 0) {
                clearInterval(interval);
                removeToast(id);
            }
        } else {
            clearInterval(interval);
        }
    }, 100);
}

function removeToast(id) {
    const idx = toasts.value.findIndex(t => t.id === id);
    if (idx > -1) toasts.value.splice(idx, 1);
}

// Watch for flash messages from backend
watch(() => page.props.flash, (flash) => {
    if (flash?.success) addToast('success', flash.success);
    if (flash?.error)   addToast('error',   flash.error);
    if (flash?.info)    addToast('info',     flash.info);
}, { immediate: true, deep: true });

// Expose for programmatic use
defineExpose({ addToast });
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 w-96 max-w-[95vw] pointer-events-none">
            <TransitionGroup name="toast" tag="div" class="flex flex-col gap-3">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto relative overflow-hidden rounded-xl shadow-2xl border border-gray-100"
                    :class="[toast.config.bg, toast.config.border]"
                >
                    <div class="flex items-start gap-3 p-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="toast.config.iconBg">
                            <span v-html="toast.config.icon"></span>
                        </div>
                        <!-- Content -->
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-sm font-bold text-gray-900">{{ toast.config.title }}</p>
                            <p class="text-sm text-gray-600 mt-0.5 leading-snug">{{ toast.message }}</p>
                        </div>
                        <!-- Close -->
                        <button @click="removeToast(toast.id)" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <!-- Progress bar -->
                    <div class="absolute bottom-0 left-0 h-0.5 transition-all duration-100 ease-linear"
                         :class="{
                             'bg-green-500': toast.type === 'success',
                             'bg-red-500': toast.type === 'error',
                             'bg-blue-500': toast.type === 'info'
                         }"
                         :style="{ width: toast.progress + '%' }">
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active {
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: all 0.25s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
</style>
