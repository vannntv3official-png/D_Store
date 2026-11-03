<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';

interface Props {
    show?: boolean;
    open?: boolean;
    title?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    open: false,
    maxWidth: 'md',
});

const isVisible = computed(() => props.show || props.open);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const close = () => {
    emit('close');
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isVisible.value) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

watch(
    isVisible,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

const maxWidthClasses = computed(() => ({
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth]));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-[#111111]/60 backdrop-blur-sm transition-opacity"
                    @click="close"
                ></div>

                <!-- Modal Dialog -->
                <div
                    class="relative bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:w-full border border-[#E5E5EA] p-6 sm:p-8"
                    :class="[maxWidthClasses]"
                    role="dialog"
                    aria-modal="true"
                >
                    <button
                        type="button"
                        class="absolute top-5 right-5 text-[#86868B] hover:text-[#111111] transition-colors p-1 rounded-full focus:outline-none"
                        @click="close"
                        aria-label="Close dialog"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <h3 v-if="title" class="text-base font-black text-[#111111] tracking-tight mb-4">
                        {{ title }}
                    </h3>

                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
