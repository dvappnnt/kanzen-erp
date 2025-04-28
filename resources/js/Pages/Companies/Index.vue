<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Data/Table.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import { ref, onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import axios from "@/axios";
import moment from "moment";

const modelName = "companies";
const modelData = ref({ data: [], links: [] });
const isLoading = ref(false);

// Access appSettings from Inertia.js page props
const { appSettings } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

// Define Header Actions
const headerActions = ref([
    {
        text: "Export",
        url: `/${modelName}/export`,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded",
    },
    {
        text: "Create",
        url: `/${modelName}/create`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: primaryColor.value, // Dynamically set background color
        })),
    },
]);

// Define Table Columns
const columns = ref([
    {
        label: "Name",
        value: "name",
        has_avatar: true,
        avatar: (row) => (row.avatar ? `/storage/${row.avatar}` : null), // Adjust for your base URL
    },
    { label: "Address", value: "address" },
    { label: "Created At", value: (row) => moment(row.created_at).fromNow() },
    { label: "Actions" },
]);

const mapCustomButtons = (row) => ({
    ...row,
    viewUrl: `/${modelName}/${row.id}`,
    editUrl: `/${modelName}/${row.id}/edit`,
    deleteUrl: `/api/${modelName}/${row.id}`,
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
    <AppLayout :title="modelName.charAt(0).toUpperCase() + modelName.slice(1)">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ modelName.charAt(0).toUpperCase() + modelName.slice(1) }}
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
                            :placeholder="`Search ${modelName}...`"
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
