<template>
    <div class="px-9 py-9">
        <h2 class="text-lg font-semibold mb-4">{{ title }}</h2>
        <div class="space-y-4">
            <div v-for="(row, index) in rowDetails" :key="index" class="flex items-start">
                <div class="w-1/3 text-sm font-medium text-gray-500">
                    {{ row.label }}
                </div>
                <div :class="getRowClass(row, modelData)">
                    {{ getRowText(row, modelData) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const getValue = (row, modelData) => {
    if (typeof row.value === 'function') {
        return row.value(modelData);
    }
    return row.format ? row.format(modelData[row.value]) : modelData[row.value] || '-';
};

function getRowText(row, modelData) {
    if (typeof row.render === 'function') {
        const result = row.render(modelData);
        return result && typeof result === 'object' && 'text' in result ? result.text : result;
    }
    return getValue(row, modelData);
}

function getRowClass(row, modelData) {
    if (typeof row.render === 'function') {
        const result = row.render(modelData);
        return result && typeof result === 'object' && 'class' in result ? result.class : (row.class || 'text-sm text-gray-900');
    }
    return row.class || 'text-sm text-gray-900';
}

defineProps({
    title: {
        type: String,
        required: true,
    },
    modelData: {
        type: Object,
        required: true,
    },
    rowDetails: {
        type: Array,
        required: true,
    },
});
</script>
