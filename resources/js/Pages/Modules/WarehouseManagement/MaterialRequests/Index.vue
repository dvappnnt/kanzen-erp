<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
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

// Format row actions
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
    } catch (error) {
        console.error(
            "❌ Error fetching:",
            error.response?.data || error.message
        );
    } finally {
        isLoading.value = false;
    }
};

// Handlers
const handleAction = (url) => router.get(url);
const handlePagination = (url) => fetchTableData(url);

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

        <div class="max-w-12xl mt-6">
            <div class="bg-white shadow rounded-lg overflow-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Reference No</th>
                            <th class="px-4 py-2 text-left">Warehouse</th>
                            <th class="px-4 py-2 text-left">Requested By</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="bg-white divide-y divide-gray-100"
                        v-if="!isLoading"
                    >
                        <tr v-for="row in modelData.data" :key="row.id">
                            <td class="px-4 py-2">
                                <a
                                    @click="handleAction(row.viewUrl)"
                                    class="text-blue-600 hover:underline cursor-pointer"
                                >
                                    {{ row.reference_no }}
                                </a>
                            </td>
                            <td class="px-4 py-2">
                                {{ row.warehouse?.name ?? "—" }}
                            </td>
                            <td class="px-4 py-2">
                                {{ row.requested_by?.name ?? "—" }}
                            </td>
                            <td class="px-4 py-2">
                                <span :class="getStatusPillClass(row.status)">
                                    {{ humanReadable(row.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                {{ moment(row.created_at).format("L, LT") }}
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <button
                                    @click="handleAction(row.viewUrl)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm"
                                >
                                    View
                                </button>
                                <button
                                    @click="handleAction(row.editUrl)"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm"
                                >
                                    Edit
                                </button>

                                <template v-if="row.status === 'approved'">
                                    <button
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                                        @click="
                                            handleAction(
                                                `/internal-transfers/create?material_request_id=${row.id}`
                                            )
                                        "
                                    >
                                        Generate Internal Transfer
                                    </button>
                                    <button
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm"
                                        @click="
                                            handleAction(
                                                `/purchase-requests/create?material_request_id=${row.id}`
                                            )
                                        "
                                    >
                                        Generate PR
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="modelData.links?.length"
                class="mt-4 flex flex-wrap justify-center gap-2"
            >
                <button
                    v-for="link in modelData.links"
                    :key="link.label"
                    :disabled="!link.url"
                    @click="handlePagination(link.url)"
                    v-html="link.label"
                    class="px-3 py-1 rounded border text-sm"
                    :class="[
                        link.active ? 'bg-black text-white' : 'bg-white hover:bg-gray-100',
                        !link.url && 'text-gray-400 cursor-not-allowed',
                    ]"
                />
            </div>
        </div>
    </AppLayout>
</template>
