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
    </label>
</template>
