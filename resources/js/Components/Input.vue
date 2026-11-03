<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: string | number | null;
    id?: string;
    label?: string;
    type?: string;
    placeholder?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    disabled?: boolean;
    autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    id: () => 'input-' + Math.random().toString(36).substring(2, 9),
    type: 'text',
    placeholder: '',
    error: '',
    hint: '',
    required: false,
    disabled: false,
    autocomplete: 'off',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
}>();

const hasError = computed(() => !!props.error);

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', props.type === 'number' ? (target.value === '' ? '' : Number(target.value)) : target.value);
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

        <div class="relative rounded-xl">
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :autocomplete="autocomplete"
                @input="onInput"
                class="w-full px-4 py-3 bg-white text-[#1D1D1F] text-sm rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#111111] focus:border-[#111111] disabled:bg-[#F5F5F7] disabled:text-[#86868B] disabled:cursor-not-allowed placeholder-[#86868B]/60"
                :class="[
                    hasError
                        ? 'border-[#DC2626] focus:ring-[#DC2626] focus:border-[#DC2626]'
                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                ]"
            />
        </div>

        <p v-if="error" class="text-xs text-[#DC2626] mt-1 font-medium">
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-xs text-[#86868B] mt-1">
            {{ hint }}
        </p>
    </div>
</template>
