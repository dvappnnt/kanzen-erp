<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const page = usePage();
const modelName = "categories";
const modelData = computed(() => page.props.modelData || {});

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
            color: buttonPrimaryTextColor.value
        })),
    },
]);

const columns = ref([
    {
        label: "Related Model",
        value: (row) =>
            (row.related_model ?? "None")
                .replace(/[-_]/g, " ")
                .split(" ")
                .map(
                    (word) =>
                        word.charAt(0).toUpperCase() +
                        word.slice(1).toLowerCase()
                )
                .join(" "),
    },
    { label: "Parent Category", value: (row) => row.parent?.name || "N/A" },
    { label: "Name", value: "name" },
    { label: "Created At", value: (row) => moment(row.created_at).fromNow() },
]);

// Get initials for avatar fallback
const getInitials = (name) => {
    if (!name) return "N/A";
    return name
        .split(" ")
        .map((n) => n[0]?.toUpperCase())
        .slice(0, 2)
        .join("");
};
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

        <div class="max-w-7xl mx-auto">
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
                            <span>{{ modelData[col.value] || "N/A" }}</span>
                        </div>
                    </template>

                    <template v-else>
                        <p>
                            <strong class="mr-1">{{ col.label }}:</strong>
                            <span v-if="typeof col.value === 'function'">
                                {{ col.value(modelData) }}
                            </span>
                            <span v-else>
                                {{ modelData[col.value] || "N/A" }}
                            </span>
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
