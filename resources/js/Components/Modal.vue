<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: '2xl' },
    closeable: { type: Boolean, default: true },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

watch(() => props.show, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
    }[props.maxWidth];
});
</script>

<template>
    <Teleport to="body">
        <div v-show="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 transition-all duration-300">
            <!-- Backdrop -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="fixed inset-0 bg-gray-900/60 backdrop-blur-md" @click="close"></div>
            </Transition>

            <!-- Modal Panel -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-8 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-8 sm:scale-95"
            >
                <div 
                    v-show="show" 
                    class="bg-white rounded-[2.5rem] shadow-2xl transform transition-all sm:w-full relative z-50 flex flex-col max-h-[90vh] border border-white/20"
                    :class="[maxWidthClass]"
                >
                    <!-- Header -->
                    <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between flex-shrink-0">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">{{ title || 'Form Action' }}</h3>
                            <div class="h-1.5 w-12 bg-indigo-600 rounded-full mt-1.5"></div>
                        </div>
                        <button @click="close" class="p-2.5 hover:bg-gray-100 rounded-2xl transition-all text-gray-400 hover:text-gray-900 active:scale-90">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Scrollable Container -->
                    <div class="px-8 py-8 overflow-y-auto custom-scrollbar flex-grow">
                        <slot />
                    </div>

                    <!-- Optional Footer Slot -->
                    <div v-if="$slots.footer" class="px-8 py-6 bg-gray-50/50 border-t border-gray-50 flex items-center justify-end gap-3 flex-shrink-0">
                        <slot name="footer" />
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
