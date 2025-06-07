<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Data/Table.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import { ref, onMounted, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import axios from "@/axios";
import moment from "moment";
import { useColors } from "@/Composables/useColors";
import {
    formatName,
    formatDate,
    getStatusPillClass,
    humanReadable,
    formatTime,
} from "@/utils/global";

const modelName = "employee-overtimes";
const modelData = ref({ data: [], links: [] });
const isLoading = ref(false);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

// Define Header Actions
const headerActions = ref([
    {
        text: "Create",
        url: `/${modelName}/create`,
        inertia: true,
        class: "px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: buttonPrimaryBgColor.value,
            color: buttonPrimaryTextColor.value,
        })),
    },
]);

// Define Table Columns
const columns = ref([
    {
        label: "Employee",
        value: (row) => row.employee.formal_full_name,
        class: "font-semibold",
        has_avatar: true,
        avatar: (row) => (row.employee.avatar ? `/storage/${row.employee.avatar}` : null), // Adjust for your base URL
        uri: (row) => route("employee-overtimes.show", row.id),
    },
    {
        label: "Date",
        render: (row) => ({
            text: formatDate("M d Y", row.date),
            icon: "mdi-calendar-outline",
        }),
    },
    {
        label: "Start Time",
        value: (row) => formatTime(row.start_time),
    },
    {
        label: "End Time",
        value: (row) => formatTime(row.end_time),
    },
    {
        label: "Minutes Rendered",
        value: (row) => row.minutes_rendered,
        render: (row) => ({
            text: `${row.minutes_rendered} minute(s)`,
            class: "font-semibold",
            icon: "mdi-clock-outline",
        }),
    },
    {
        label: "Status",
        value: "status",
        render: (row) => ({
            text: humanReadable(row.status),
            class: getStatusPillClass(row.status),
        }),
    },
    { label: "Created At", value: (row) => moment(row.created_at).fromNow() },
    { label: "Actions" },
]);

const mapCustomButtons = (row) => ({
    ...row,
    viewUrl: `/${modelName}/${row.id}`,
    editUrl: `/${modelName}/${row.id}/edit`,
    restoreUrl: row.deleted_at ? `/api/${modelName}/${row.id}/restore` : null,
    customUrls: [],
});

// Fetch Table Data
const fetchTableData = async (url = `/api/${modelName}`) => {
    isLoading.value = true;
    try {
        const response = await axios.get(url);
        modelData.value = {
            ...response.data,
            data: response.data.data.map(mapCustomButtons),
        };
    } catch (error) {
        console.error(
            "Error fetching data:",
            error.response?.data || error.message
        );
    } finally {
        isLoading.value = false;
    }
};

// Pagination Handling
const handlePagination = (url) => {
    fetchTableData(url);
};

// Table Actions
const handleAction = async ({ action, row }) => {
    try {
        if (action === "view" && row.viewUrl) {
            router.get(row.viewUrl);
        } else if (action === "edit" && row.editUrl) {
            router.get(row.editUrl);
        } else if (action === "delete") {
            await axios.delete(row.deleteUrl);
            await fetchTableData();
        } else if (action === "restore") {
            await axios.post(row.restoreUrl);
            await fetchTableData();
        }
    } catch (error) {
        console.error("Action failed:", error.response?.data || error.message);
        alert("Action failed.");
    }
};

// Initialize Table Data
onMounted(() => fetchTableData());
</script>

<template>
    <AppLayout :title="`${formatName(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ formatName(modelName) }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-12xl">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="mb-4">
                        <Autocomplete
                            :searchUrl="`/api/autocomplete/${modelName}`"
                            :modelName="modelName"
                            :placeholder="`Search ${humanReadable(
                                modelName
                            ).toLowerCase()}...`"
                            :mapCustomButtons="mapCustomButtons"
                            @select="modelData = $event"
                        />
                    </div>

                    <Table
                        :data="modelData"
                        :columns="columns"
                        :modelName="modelName"
                        :isLoading="isLoading"
                        @paginate="handlePagination"
                        @action="handleAction"
                        @refresh="fetchTableData"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
