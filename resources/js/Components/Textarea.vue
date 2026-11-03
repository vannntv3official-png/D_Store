<script setup lang="ts">
interface Props {
    modelValue?: string | null;
    id?: string;
    label?: string;
    placeholder?: string;
    error?: string;
    rows?: number | string;
    required?: boolean;
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    id: () => 'textarea-' + Math.random().toString(36).substring(2, 9),
    placeholder: '',
    error: '',
    rows: 4,
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const onInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLTextAreaElement).value);
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

        <textarea
            :id="id"
            :rows="rows"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            @input="onInput"
            class="w-full px-4 py-3 bg-white text-[#1D1D1F] text-sm rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#111111] focus:border-[#111111] disabled:bg-[#F5F5F7] disabled:text-[#86868B] disabled:cursor-not-allowed placeholder-[#86868B]/60 resize-y"
            :class="[
                error
                    ? 'border-[#DC2626] focus:ring-[#DC2626] focus:border-[#DC2626]'
                    : 'border-[#E5E5EA] hover:border-[#86868B]',
            ]"
        ></textarea>

        <p v-if="error" class="text-xs text-[#DC2626] mt-1 font-medium">
            {{ error }}
        </p>
    </div>
</template>
