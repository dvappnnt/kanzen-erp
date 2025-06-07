<template>
    <div
        class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-6 px-4 sm:px-9 mb-6 text-center sm:text-left"
    >
        <!-- Avatar and Info -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 w-full">
            <!-- Avatar -->
            <div
                class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-2xl mx-auto sm:mx-0"
            >
                <img
                    v-if="getAvatarPath(modelData)"
                    :src="getAvatarPath(modelData)"
                    alt="Avatar"
                    class="w-24 h-24 rounded-full object-cover"
                />
                <span v-else>
                    {{ getInitials(modelData.name) }}
                </span>
            </div>

            <!-- Info -->
            <div class="flex flex-col items-center sm:items-start w-full">
                <template v-for="col in columns" :key="col.label || 'qr'">
                    <template v-if="!col.has_qr">
                        <p
                            v-if="getValue(col, modelData)"
                            :class="col.class || 'text-gray-600 font-semibold'"
                            class="mb-1"
                        >
                            {{ getValue(col, modelData) }}
                        </p>
                        <p v-else-if="col.label" class="text-gray-500 mb-1">
                            No {{ col.label.toLowerCase() }} provided
                        </p>
                    </template>
                </template>

                <!-- Timestamp Below Info -->
                <p class="text-sm text-gray-400 mt-1 block sm:hidden">
                    Created {{ moment(modelData.created_at).fromNow() }}
                </p>
            </div>
        </div>

        <!-- QR Code -->
        <div v-if="qrColumn" class="flex justify-center sm:justify-end">
            <QRCode
                :value="qrColumn.qr_data(modelData)"
                :size="128"
                level="M"
                render-as="svg"
                class="bg-white p-2 rounded-lg shadow-sm"
            />
        </div>
    </div>
</template>

<script setup>
import QRCode from "qrcode.vue";
import moment from "moment";
import { computed } from "vue";

const getInitials = (name) => {
    if (!name) return "N/A";
    return name
        .split(" ")
        .map((n) => n[0]?.toUpperCase())
        .slice(0, 2)
        .join("");
};

const getValue = (col, modelData) => {
    if (typeof col.value === "function") {
        return col.value(modelData);
    }
    return modelData[col.value] || null;
};

const getAvatarPath = (modelData) => {
    // Check for custom avatar path in columns
    const avatarColumn = props.columns.find(col => col.avatar);
    if (avatarColumn && typeof avatarColumn.avatar === 'function') {
        const customPath = avatarColumn.avatar(modelData);
        if (customPath) {
            return customPath.startsWith('/storage/') ? customPath : `/storage/${customPath}`;
        }
    }
    
    // Fallback to default avatar path
    return modelData.avatar ? `/storage/${modelData.avatar}` : null;
};

const props = defineProps({
    modelData: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const qrColumn = computed(() => props.columns.find((col) => col.has_qr));
</script>

<style scoped>
/* Optional scroll protection for QR */
.qr-container {
    max-width: 100%;
    overflow-x: auto;
}
</style>
