<script setup>
import { onMounted, ref } from 'vue';
import { computed } from 'vue';
import { useColors } from "@/Composables/useColors";

const { inputActiveBgColor } = useColors();

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="border-gray-300 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] rounded-md shadow-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :style="{ '--primary-color': inputActiveBgColor }"
    >
</template>
