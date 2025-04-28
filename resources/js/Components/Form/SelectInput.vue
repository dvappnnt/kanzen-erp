<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const primaryColor = computed(() => page.props.appSettings?.input_active_bg_color || "#1F2937");
</script>

<template>
    <select
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)]"
        :style="{ '--primary-color': primaryColor }"
    >
        <slot />
    </select>
</template> 