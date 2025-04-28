<script setup>
import { computed, useSlots } from "vue";
import SectionTitle from "@/Components/SectionTitle.vue";

defineEmits(["submitted"]);

// Props for dynamic configuration
defineProps({
    formClasses: {
        type: String,
        default: "md:grid md:grid-cols-3 md:gap-6", // Default classes for grid layout
    },
    colSpan: {
        type: String,
        default: "md:col-span-3", // Full width by default
    },
});

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
    <div :class="formClasses">
        <div>
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="px-4 py-5 bg-white sm:p-6 shadow"
                    :class="
                        hasActions
                            ? 'sm:rounded-tl-md sm:rounded-tr-md'
                            : 'sm:rounded-md'
                    "
                >
                    <SectionTitle class="mb-4">
                        <template #title>
                            <slot name="title" />
                        </template>
                        <template #description>
                            <slot name="description" />
                        </template>
                    </SectionTitle>

                    <div class="md:grid md:grid-cols-1 md:gap-4">
                        <slot name="form" />
                    </div>
                </div>

                <div
                    v-if="hasActions"
                    class="flex items-center justify-end px-4 py-3 bg-gray-50 text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md"
                >
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
