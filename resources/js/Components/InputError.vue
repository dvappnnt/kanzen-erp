<script setup>
defineProps({
    message: [String, Array],
});
</script>

<template>
    <div v-show="message">
        <!-- If message is an array with more than one error, display as bulleted list -->
        <ul v-if="Array.isArray(message) && message.length > 1" class="list-disc list-inside text-sm text-red-600">
            <li v-for="(msg, index) in message" :key="index">
                {{ msg }}
            </li>
        </ul>
        <!-- If message is a single error or a string -->
        <p v-else class="text-sm text-red-600">
            {{ Array.isArray(message) ? message[0] : message }}
        </p>
    </div>
</template>
