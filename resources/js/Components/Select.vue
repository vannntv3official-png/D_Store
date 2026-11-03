<script setup lang="ts">
import { computed } from 'vue';

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
    (e: 'update:modelValue', value: string): void;
}>();

const onChange = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLSelectElement).value);
};
</script>

<template>
    <div class="w-full space-y-1.5">
        <label
            v-if="label"
            :for="id"
            class="block text-xs font-semibold tracking-wide text-[#1D1D1F] uppercase"
        >
            {{ label }}
            <span v-if="required" class="text-[#DC2626] ml-0.5">*</span>
        </label>

        <div class="relative">
            <select
                :id="id"
                :value="modelValue"
                :required="required"
                :disabled="disabled"
                @change="onChange"
                class="w-full appearance-none px-4 py-3 bg-white text-[#1D1D1F] text-sm rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#111111] focus:border-[#111111] disabled:bg-[#F5F5F7] disabled:text-[#86868B] disabled:cursor-not-allowed cursor-pointer pr-10"
                :class="[
                    error
                        ? 'border-[#DC2626] focus:ring-[#DC2626] focus:border-[#DC2626]'
                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                ]"
            >
                <option v-if="placeholder" value="" disabled :selected="!modelValue">
                    {{ placeholder }}
                </option>
                <option
                    v-for="opt in options"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-[#86868B]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <p v-if="error" class="text-xs text-[#DC2626] mt-1 font-medium">
            {{ error }}
        </p>
    </div>
</template>
