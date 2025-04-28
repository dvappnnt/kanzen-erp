<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";

const modelName = "activity-logs";

// Access appSettings from Inertia.js page props
const { appSettings } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: primaryColor.value, // Dynamically set background color
        })),
    },
]);

const columns = ref([
    { label: "Created At", value: (row) => moment(row.created_at).fromNow() },
]);

// Get the company data from Inertia
const page = usePage();
const modelData = computed(() => page.props.modelData || {});

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
    <AppLayout
        :title="`${
            modelName.charAt(0).toUpperCase() + modelName.slice(1)
        } Details`"
    >
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Activity Logs
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-7xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-semibold mb-4">
                        Activity Log
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
        </div>
    </AppLayout>
</template>
