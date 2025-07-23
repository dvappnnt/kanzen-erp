<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Data/Table.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import { ref, onMounted, computed } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "@/axios";
import moment from "moment";
import { useColors } from "@/Composables/useColors";
import { formatName, humanReadable, getStatusPillClass } from "@/utils/global";

// Model config
const modelName = "material-requests";
const modelData = ref({ data: [], links: [] });
const isLoading = ref(false);

// Theming
const { buttonPrimaryBgColor } = useColors();

// Header action buttons
const headerActions = ref([
    {
        text: "Create",
        url: `/${modelName}/create`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: buttonPrimaryBgColor.value,
        })),
    },
]);

// Table columns
const columns = ref([
    {
        label: "Reference No",
        value: "reference_no",
        uri: (row) => route("material-requests.show", row.id),
        class: "text-blue-600 hover:underline",
        icon: "mdi-file-document-outline",
    },
    {
        label: "Warehouse",
        value: (row) => row?.warehouse?.name ?? "—",
        icon: "mdi-warehouse",
    },
    {
        label: "Requested By",
        value: (row) => row?.requested_by?.name ?? "—",
    },
    {
        label: "Status",
        value: "status",
        render: (row) => ({
            text: humanReadable(row.status),
            class: getStatusPillClass(row.status),
        }),
    },
    {
        label: "Date",
        value: (row) => moment(row.created_at).format("L, LT"),
    },
    { label: "Actions" },
]);

// Add actions per row
const mapCustomButtons = (row) => ({
    ...row,
    viewUrl: `/${modelName}/${row.id}`,
     editUrl: `/${modelName}/${row.id}/edit`,
    label: row.reference_no,
});

// Fetch paginated data
const fetchTableData = async (url = `/api/${modelName}`) => {
    isLoading.value = true;
    try {
        const response = await axios.get(url);
        const tableData = Array.isArray(response.data.data)
            ? response.data.data
            : [];

        modelData.value = {
            ...response.data,
            data: tableData.map(mapCustomButtons),
        };

        console.log("✅ Material Requests:", response.data);
    } catch (error) {
        console.error(
            "❌ Error fetching:",
            error.response?.data || error.message
        );
    } finally {
        isLoading.value = false;
    }
};

// Pagination handler
const handlePagination = (url) => fetchTableData(url);

// Action handler
const handleAction = async ({ action, row }) => {
    try {
        if (action === "view" && row.viewUrl) {
            router.get(row.viewUrl);
        }else if (action === "edit" && row.editUrl) {
            router.get(row.editUrl); // 👈 this will open Edit.vue
        }
    } catch (error) {
        alert("Action failed.");
        console.error(error);
    }
};
const handleAutocomplete = async (selected) => {
    if (!selected) {
        fetchTableData(); // fallback to all
        return;
    }

    const mapped = mapCustomButtons(selected);

    modelData.value = {
        data: [mapped],
        links: {
            first: null,
            last: null,
            prev: null,
            next: null,
        },
        meta: {
            total: 1,
            per_page: 10,
            current_page: 1,
            last_page: 1,
            from: 1,
            to: 1,
            path: `/api/${modelName}`,
        },
    };
};


onMounted(() => fetchTableData());
</script>

<template>
    <AppLayout :title="formatName(modelName)">
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
                    <!-- <div class="mb-4">
                        <Autocomplete
                            :searchUrl="`/api/autocomplete/${modelName}`"
                            :modelName="modelName"
                            :placeholder="`Search ${formatName(
                                modelName
                            ).toLowerCase()}...`"
                            :mapCustomButtons="mapCustomButtons"
                            @select="handleAutocomplete"
                        />
                    </div> -->

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
