<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'neutral' | 'success' | 'warning' | 'danger' | 'blue';
    size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'neutral',
    size: 'md',
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-[#DCFCE7] text-[#166534] border border-[#BBF7D0]';
        case 'warning':
            return 'bg-[#FEF3C7] text-[#92400E] border border-[#FDE68A]';
        case 'danger':
            return 'bg-[#FEE2E2] text-[#991B1B] border border-[#FECACA]';
        case 'blue':
            return 'bg-[#E0F2FE] text-[#0369A1] border border-[#BAE6FD]';
        case 'neutral':
        default:
            return 'bg-[#F5F5F7] text-[#1D1D1F] border border-[#E5E5EA]';
    }
});

const sizeClasses = computed(() => {
    return props.size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs';
});
</script>

<template>
    <span
        class="inline-flex items-center font-medium rounded-full uppercase tracking-wider select-none"
        :class="[variantClasses, sizeClasses]"
    >
        <slot />
    </span>
</template>
