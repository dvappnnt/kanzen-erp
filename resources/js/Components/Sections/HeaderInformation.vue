<template>
    <div class="flex justify-between items-center mb-3 px-9">
        <h3 class="text-lg font-semibold">
            {{ title }}
        </h3>
        <p class="text-gray-500">
            Created
            {{ moment(modelData.created_at).fromNow() || "N/A" }}
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