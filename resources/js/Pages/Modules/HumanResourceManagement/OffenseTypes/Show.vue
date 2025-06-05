<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, humanReadable } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const page = usePage();
const modelName = "offense-types";
const modelData = computed(() => page.props.modelData || {});

const formatModelName = (name) => {
    if (name.endsWith("ies")) {
        return (
            name.slice(0, -3).charAt(0).toUpperCase() + name.slice(1, -3) + "y"
        );
    }
    return name.slice(0, -1).charAt(0).toUpperCase() + name.slice(1, -1);
};
const formattedTitle = formatModelName(modelName);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
    {
        text: "Edit",
        url: `/${modelName}/${modelData.value.id}/edit`,
        inertia: true,
        class: "px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: buttonPrimaryBgColor.value,
            color: buttonPrimaryTextColor.value,
        })),
    },
]);

const columns = [
    { label: "Name", value: "name" },
    { label: "Created At", value: (row) => moment(row.created_at).fromNow() },
    { label: "Updated At", value: (row) => moment(row.updated_at).fromNow() },
];
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">
                {{ singularizeAndFormat(modelName) }}
                Details
            </h3>
            <div v-for="col in columns" :key="col.label">
                <template v-if="col.has_avatar">
                    <div class="flex items-center mb-2">
                        <img
                            v-if="col.avatar(modelData)"
                            :src="col.avatar(modelData)"
                            alt="Avatar"
                            class="w-10 h-10 rounded-full object-cover mr-4"
                        />
                        <div
                            v-else
                            class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold mr-4"
                        >
                            {{ getInitials(modelData[col.value]) }}
                        </div>
                        <span>{{ modelData[col.value] || "-" }}</span>
                    </div>
                </template>

                <template v-else>
                    <p>
                        <strong class="mr-1">{{ col.label }}:</strong>
                        <span v-if="typeof col.value === 'function'">
                            {{ col.value(modelData) }}
                        </span>
                        <span v-else>
                            {{ modelData[col.value] || "-" }}
                        </span>
                    </p>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
