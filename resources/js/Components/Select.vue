<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { ChevronDown, Check, Search } from 'lucide-vue-next';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    modelValue?: string | number | null;
    id?: string;
    label?: string;
    options: Option[];
    placeholder?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    id: () => 'select-' + Math.random().toString(36).substring(2, 9),
    placeholder: 'Select an option',
    error: '',
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', value: string | number): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const selectRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const selectedOption = computed(() => {
    return props.options.find((opt) => String(opt.value) === String(props.modelValue)) ?? null;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.options;
    }
    const q = searchQuery.value.toLowerCase();
    return props.options.filter((opt) => opt.label.toLowerCase().includes(q));
});

const toggle = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        setTimeout(() => searchInputRef.value?.focus(), 50);
    }
};

const close = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const select = (value: string | number) => {
    emit('update:modelValue', value);
    emit('change', value);
    close();
};

const handleClickOutside = (event: MouseEvent) => {
    if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
        close();
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isOpen.value) {
        close();
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
    <div ref="selectRef" class="w-full space-y-1.5 relative text-left">
        <!-- Label -->
        <label
            v-if="label"
            :for="id"
            class="block text-xs font-semibold tracking-wide text-[#1D1D1F] uppercase"
        >
            {{ label }}
            <span v-if="required" class="text-[#DC2626] ml-0.5">*</span>
        </label>

        <!-- Hidden Input for Form Compatibility -->
        <input
            type="hidden"
            :id="id"
            :name="id"
            :value="modelValue"
            :required="required"
        />

        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggle"
            :disabled="disabled"
            :aria-expanded="isOpen"
            class="w-full px-4 py-3 bg-white text-[#1D1D1F] text-sm rounded-xl border transition-[border-color,box-shadow,background-color] duration-150 flex items-center justify-between gap-3 text-left shadow-sm cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-[#111111]"
            :class="[
                disabled ? 'bg-[#F5F5F7] text-[#86868B] cursor-not-allowed border-[#E5E5EA]' : '',
                error
                    ? 'border-[#DC2626] ring-1 ring-[#DC2626]'
                    : isOpen
                    ? 'border-[#111111] ring-2 ring-[#111111]/10'
                    : 'border-[#E5E5EA] hover:border-[#86868B]',
            ]"
        >
            <span
                class="truncate font-medium"
                :class="!selectedOption ? 'text-[#86868B]' : 'text-[#1D1D1F]'"
            >
                {{ selectedOption?.label || placeholder }}
            </span>
            <ChevronDown
                class="w-4 h-4 text-[#86868B] shrink-0 transition-transform duration-200"
                :class="{ 'rotate-180 text-[#111111]': isOpen }"
            />
        </button>

        <!-- Dropdown Menu Popover -->
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
                class="absolute left-0 right-0 z-50 mt-1.5 bg-white/95 backdrop-blur-xl border border-[#E5E5EA] rounded-2xl shadow-2xl p-2 focus:outline-none text-xs d-shadow-float"
            >
                <!-- Search bar if more than 7 options -->
                <div v-if="options.length > 7" class="relative mb-2 px-1">
                    <Search class="w-3.5 h-3.5 text-[#86868B] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search..."
                        class="w-full pl-8 pr-3 py-1.5 bg-[#F5F5F7] text-xs text-[#1D1D1F] rounded-lg border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                    />
                </div>

                <!-- Options List -->
                <div class="space-y-0.5 max-h-60 overflow-y-auto d-scrollbar pr-1">
                    <button
                        v-for="opt in filteredOptions"
                        :key="opt.value"
                        type="button"
                        @click="select(opt.value)"
                        role="option"
                        :aria-selected="String(opt.value) === String(modelValue)"
                        class="w-full text-left px-3 py-2 rounded-xl transition-[background-color,color] duration-100 flex items-center justify-between group cursor-pointer touch-manipulation select-none"
                        :class="[
                            String(opt.value) === String(modelValue)
                                ? 'bg-[#111111] text-white font-semibold'
                                : 'text-[#1D1D1F] hover:bg-[#F5F5F7] hover:text-[#111111]',
                        ]"
                    >
                        <span class="truncate">{{ opt.label }}</span>
                        <Check
                            v-if="String(opt.value) === String(modelValue)"
                            class="w-3.5 h-3.5 text-white shrink-0 ml-2"
                        />
                    </button>

                    <div
                        v-if="filteredOptions.length === 0"
                        class="py-4 text-center text-xs text-[#86868B]"
                    >
                        No results found
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Error Message -->
        <p v-if="error" class="text-xs text-[#DC2626] mt-1 font-medium">
            {{ error }}
        </p>
    </div>
</template>
