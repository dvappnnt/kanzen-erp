<template>
    <div class="flex flex-col px-9 mb-6">
        <!-- Detail Grid -->
        <div :class="`grid gap-y-4 gap-x-8 w-full ${gridClass}`">
            <div
                v-for="col in columns"
                :key="col.label || col.value"
                class="flex flex-col"
            >
                <span class="text-sm text-gray-400 uppercase font-medium">{{
                    col.label
                }}</span>
                <span
                    :class="
                        (() => {
                            if (col.render) {
                                const rendered = col.render(modelData);
                                return `inline-block ${
                                    rendered?.class ||
                                    col.class ||
                                    'text-gray-700 font-semibold'
                                }`;
                            }
                            return col.class
                                ? `inline-block ${col.class}`
                                : 'text-gray-700 font-semibold inline-block';
                        })()
                    "
                >
                    {{ getValue(col, modelData) || "—" }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import QRCode from "qrcode.vue";
import { computed } from "vue";

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
    columnsPerRow: {
        type: Number,
        default: 2, // you can set this to 1, 2, or 3
    },
});

const getInitials = (name) => {
    if (!name) return "N/A";
    return name
        .split(" ")
        .map((n) => n[0]?.toUpperCase())
        .slice(0, 2)
        .join("");
};

const getValue = (col, modelData) => {
    if (col.render) {
        const rendered = col.render(modelData);
        return typeof rendered === "object" ? rendered.text : rendered;
    }

    if (typeof col.value === "function") {
        return col.value(modelData);
    }

    return modelData[col.value] ?? null;
};

const qrColumn = computed(() => props.columns.find((col) => col.has_qr));

const gridClass = computed(() => {
    const cols = props.columnsPerRow;
    return `grid-cols-1 sm:grid-cols-${Math.min(cols, 2)} md:grid-cols-${cols}`;
});
</script>
