<script setup>
defineProps({
    value: String,
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label class="block font-medium text-sm text-gray-700">
        <span>
            <span v-if="value">{{ value }}</span>
            <slot v-else />
        </span>
        <span v-if="required" class="text-red-500 ml-1">*</span>
        <span v-else class="text-gray-500 ml-1">(optional)</span>
    </label>
</template>
