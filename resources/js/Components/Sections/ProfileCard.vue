<template>
    <div class="flex justify-between items-start px-9 mb-6">
        <div class="flex items-center">
            <!-- Avatar -->
            <div
                class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-2xl mr-6"
            >
                <img
                    v-if="modelData.avatar"
                    :src="`/storage/${modelData.avatar}`"
                    alt="Avatar"
                    class="w-24 h-24 rounded-full object-cover"
                />
                <span v-else>
                    {{ getInitials(modelData.name) }}
                </span>
            </div>

            <!-- Dynamic Content -->
            <div>
                <div v-for="col in columns" :key="col.label || 'qr'">
                    <template v-if="!col.has_qr && getValue(col, modelData)">
                        <p :class="col.class || 'text-gray-600 font-semibold'">
                            {{ getValue(col, modelData) }}
                        </p>
                    </template>
                    <template v-else-if="!col.has_qr && col.label">
                        <p class="text-gray-500">
                            No {{ col.label.toLowerCase() }} provided
                        </p>
                    </template>
                </div>
            </div>
        </div>
        <!-- QR Code on the right if available -->
        <template v-if="qrColumn">
            <div class="flex flex-col items-end">
                <QRCode
                    :value="qrColumn.qr_data(modelData)"
                    :size="128"
                    level="M"
                    render-as="svg"
                    class="bg-white p-2 rounded-lg shadow-sm"
                />
            </div>
        </template>
    </div>
</template>

<script setup>
import QRCode from 'qrcode.vue';
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

const qrColumn = computed(() => props.columns.find(col => col.has_qr));
</script>
