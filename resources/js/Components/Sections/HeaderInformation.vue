<template>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-3 px-4 sm:px-9 text-center sm:text-left">
        <h3 class="text-lg font-semibold w-full sm:w-auto">
            {{ title }}
        </h3>
        <p class="text-gray-500 mt-2 sm:mt-0 hidden sm:block">
            Created {{ moment(modelData.created_at).fromNow() || "N/A" }}
        </p>
    </div>
</template>

<script setup>
import moment from "moment";

defineProps({
    title: {
        type: String,
        required: true,
    },
    modelData: {
        type: Object,
        required: true,
    },
});
</script>
