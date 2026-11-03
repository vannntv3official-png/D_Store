<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    modelValue: string | number;
    options: Option[];
    placeholder?: string;
    align?: 'left' | 'right';
    minWidth?: string;
    size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select option',
    align: 'right',
    minWidth: 'w-48 sm:w-56',
    size: 'sm',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', value: string | number): void;
}>();

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedOption = computed(() => {
    return props.options.find((opt) => opt.value === props.modelValue) ?? null;
});

const highlightedIndex = ref(-1);

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        highlightedIndex.value = props.options.findIndex((opt) => opt.value === props.modelValue);
        if (highlightedIndex.value < 0) highlightedIndex.value = 0;
    }
};

const close = () => {
    isOpen.value = false;
    highlightedIndex.value = -1;
};

const select = (value: string | number) => {
    emit('update:modelValue', value);
    emit('change', value);
    close();
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        close();
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (!isOpen.value) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key) && dropdownRef.value?.contains(document.activeElement)) {
            event.preventDefault();
            toggle();
        }
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        close();
    } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = (highlightedIndex.value + 1) % props.options.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = (highlightedIndex.value - 1 + props.options.length) % props.options.length;
    } else if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (highlightedIndex.value >= 0 && highlightedIndex.value < props.options.length) {
            select(props.options[highlightedIndex.value].value);
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggle"
            :aria-expanded="isOpen"
            class="inline-flex items-center justify-between gap-2.5 rounded-xl border border-[#E5E5EA] bg-white text-[#1D1D1F] font-semibold transition-[transform,background-color,border-color,box-shadow] duration-150 ease-out hover:border-[#111111] hover:bg-[#FBFBFC] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#111111] cursor-pointer touch-manipulation shadow-sm select-none"
            :class="[
                size === 'sm' ? 'px-3.5 py-2 text-xs' : 'px-4 py-2.5 text-sm',
                isOpen ? 'border-[#111111] ring-2 ring-[#111111]/10' : '',
            ]"
        >
            <span class="truncate">
                {{ selectedOption?.label || placeholder }}
            </span>
            <ChevronDown
                class="w-3.5 h-3.5 text-[#86868B] transition-transform duration-200 shrink-0"
                :class="{ 'rotate-180 text-[#111111]': isOpen }"
            />
        </button>

        <!-- Floating Popover Menu -->
        <Transition
            enter-active-class="transition-[transform,opacity] duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-[transform,opacity] duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1.5 bg-white/95 backdrop-blur-xl border border-[#E5E5EA] rounded-2xl shadow-2xl p-1.5 focus:outline-none text-xs d-shadow-float"
                :class="[
                    align === 'right' ? 'right-0 origin-top-right' : 'left-0 origin-top-left',
                    minWidth,
                ]"
                role="listbox"
            >
                <div class="space-y-0.5 max-h-64 overflow-y-auto d-scrollbar pr-1">
                    <button
                        v-for="(option, idx) in options"
                        :key="option.value"
                        type="button"
                        @click="select(option.value)"
                        @mouseenter="highlightedIndex = idx"
                        role="option"
                        :aria-selected="option.value === modelValue"
                        class="w-full text-left px-3 py-2 rounded-xl transition-[background-color,color] duration-100 flex items-center justify-between group cursor-pointer touch-manipulation select-none"
                        :class="[
                            option.value === modelValue
                                ? 'bg-[#111111] text-white font-semibold'
                                : highlightedIndex === idx
                                ? 'bg-[#F5F5F7] text-[#111111]'
                                : 'text-[#1D1D1F] hover:bg-[#F5F5F7] hover:text-[#111111]',
                        ]"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <Check
                            v-if="option.value === modelValue"
                            class="w-3.5 h-3.5 text-white shrink-0 ml-2"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
