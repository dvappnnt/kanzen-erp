<script setup>
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import ResponseModal from "@/Components/ResponseModal.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "@/axios";
import { useColors } from "@/Composables/useColors";

// Props passed from the parent
const props = defineProps({
    data: {
        type: Object, // Expecting paginated response object
        required: true,
    },
    columns: {
        type: Array, // Table columns
        required: true,
    },
    modelName: {
        type: String,
        default: "Data",
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

// State for Response Modal
const responseModal = ref({
    show: false,
    title: "",
    message: "",
    type: "", // success, error, info
});

// Show response modal
const showResponse = (title, message, type) => {
    responseModal.value = { show: true, title, message, type };
};

const emit = defineEmits(["select", "paginate", "action", "refresh"]);

// Emit pagination event when a page link is clicked
const handlePageChange = (url) => {
    if (url) emit("paginate", url);
};

// Modal state and data
const showModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");
const modalAction = ref(null);
const modalRow = ref(null);
const isSubmitting = ref(false);

// Open modal for delete or restore action
const openModal = (action, row) => {
    modalAction.value = action;
    modalRow.value = row;

    if (action === "delete") {
        modalTitle.value = `Delete ${row.name}?`;
        modalContent.value =
            "Are you sure you want to delete this item? This action cannot be undone.";
    } else if (action === "restore") {
        modalTitle.value = `Restore ${row.name}?`;
        modalContent.value = "Are you sure you want to restore this item?";
    }

    showModal.value = true;
};

// Confirm action (delete or restore)
const confirmAction = async () => {
    if (!modalAction.value || !modalRow.value) return;
    isSubmitting.value = true;
    try {
        const response =
            modalAction.value === "delete" || modalAction.value === "restore"
                ? await axios({
                      method:
                          modalAction.value === "delete" ? "delete" : "patch",
                      url:
                          modalAction.value === "delete"
                              ? modalRow.value.deleteUrl
                              : modalRow.value.restoreUrl,
                  })
                : await axios({
                      method: modalRow.value.customUrls.find(
                          (url) => url.label === modalTitle.value
                      )?.method,
                      url: modalRow.value.customUrls.find(
                          (url) => url.label === modalTitle.value
                      )?.link,
                  });

        emit("refresh");

        showResponse(
            "Success",
            response.data.message || "Action completed successfully!",
            "success"
        );
    } catch (error) {
        console.error("Action failed:", error.response?.data || error.message);
        showResponse(
            "Error",
            error.response?.data?.message || "Something went wrong.",
            "error"
        );
    } finally {
        isSubmitting.value = false;
        showModal.value = false;
    }
};

// Get initials for avatar fallback
const getInitials = (name) => {
    if (!name) return "N/A";
    return name
        .split(" ")
        .map((n) => n[0]?.toUpperCase())
        .slice(0, 2)
        .join("");
};

// Handle search selection
const handleSearchSelect = async (selectedModelData) => {
    try {
        const response = await axios.get(
            `api/${props.modelName}/${selectedModelData.id}`
        );
        const fetchedData = response.data;

        const responseData = {
            data: [fetchedData].map((row) => ({
                ...row,
                viewUrl: `/${props.modelName}/${row.id}`,
                editUrl: `/${props.modelName}/${row.id}/edit`,
                deleteUrl: `/api/${props.modelName}/${row.id}`,
                restoreUrl: row.deleted_at
                    ? `/api/${props.modelName}/${row.id}/restore`
                    : null,
            })),
            links: [],
        };

        emit("select", responseData);
    } catch (error) {
        console.error(
            "Error fetching selected model data:",
            error.response?.data || error.message
        );
        alert("Failed to load the selected data.");
    }
};

const handleCustomAction = async (action, row) => {
    try {
        if (action.isRedirectPage) {
            // Use Inertia for navigation if the action is a redirect
            router.get(action.link);
        } else {
            // Trigger the confirmation modal for other API actions
            modalAction.value = action.method;
            modalRow.value = row;
            modalTitle.value = action.label;
            modalContent.value = `Are you sure you want to ${action.label.toLowerCase()}?`;

            showModal.value = true;
        }
    } catch (error) {
        console.error("Error handling custom action:", error);
        alert("An unexpected error occurred.");
    }
};
</script>

<template>
    <div class="overflow-x-auto">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-6">
            <svg
                class="animate-spin h-8 w-8 text-gray-500 mx-auto"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8z"
                ></path>
            </svg>
            <p class="text-gray-500 mt-2">Loading {{ modelName }}...</p>
        </div>

        <!-- No Data Found -->
        <div v-else-if="!data.data.length" class="text-center py-6">
            <p class="text-gray-500 mt-2">No {{ modelName }} found.</p>
        </div>

        <!-- Table -->
        <table
            v-else
            class="min-w-full bg-white border border-gray-200 rounded-lg"
        >
            <thead class="border-b border-gray-300">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.label"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, index) in data.data"
                    :key="index"
                    class="border-b last:border-0"
                >
                    <td
                        v-for="col in columns"
                        :key="col.label"
                        class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                    >
                        <template v-if="col.has_avatar">
                            <div class="flex items-center">
                                <img
                                    v-if="col.avatar(row)"
                                    :src="col.avatar(row)"
                                    alt="Avatar"
                                    class="w-10 h-10 rounded-full object-cover mr-4"
                                />
                                <div
                                    v-else
                                    class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold mr-4"
                                >
                                    {{ getInitials(row[col.value]) }}
                                </div>
                                <span>{{ row[col.value] || "N/A" }}</span>
                            </div>
                        </template>
                        <template v-else-if="col.label === 'Actions'">
                            <div class="flex items-center space-x-2">
                                <button
                                    v-if="row.viewUrl"
                                    @click="
                                        emit('action', { action: 'view', row })
                                    "
                                    class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                                >
                                    View
                                </button>
                                <button
                                    v-if="row.editUrl"
                                    @click="
                                        emit('action', { action: 'edit', row })
                                    "
                                    class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="row.deleteUrl"
                                    @click="openModal('delete', row)"
                                    class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                                >
                                    Delete
                                </button>
                                <button
                                    v-if="row.restoreUrl"
                                    @click="openModal('restore', row)"
                                    class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                                >
                                    Restore
                                </button>

                                <!-- Custom Actions -->
                                <template
                                    v-for="customAction in row.customUrls"
                                    :key="customAction.link"
                                >
                                    <button
                                        @click="
                                            handleCustomAction(
                                                customAction,
                                                row
                                            )
                                        "
                                        :class="customAction.class"
                                    >
                                        {{ customAction.label }}
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template v-else-if="col.render">
                            <span :class="[col.render(row).class]">
                                {{ col.render(row).text }}
                            </span>
                        </template>

                        <template v-else>
                            <span :class="col.class">
                                <template v-if="col.uri">
                                    <a
                                        :href="
                                            typeof col.uri === 'function'
                                                ? col.uri(row)
                                                : col.uri
                                        "
                                        class="inline-flex items-center space-x-1"
                                    >
                                        <i
                                            v-if="col.icon"
                                            :class="[
                                                'mdi',
                                                col.icon,
                                                'text-base',
                                            ]"
                                        />
                                        <span>
                                            {{
                                                typeof col.value === "function"
                                                    ? col.value(row)
                                                    : row[col.value] || "N/A"
                                            }}
                                        </span>
                                    </a>
                                </template>
                                <template v-else>
                                    {{
                                        typeof col.value === "function"
                                            ? col.value(row)
                                            : row[col.value] || "N/A"
                                    }}
                                </template>
                            </span>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Confirmation Modal with isSubmitting -->
        <ConfirmationModal
            :show="showModal"
            @close="!isSubmitting && (showModal = false)"
        >
            <template #title>
                {{ modalTitle }}
            </template>
            <template #content>
                {{ modalContent }}
            </template>
            <template #footer>
                <button
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 disabled:opacity-50"
                    :disabled="isSubmitting"
                    @click="!isSubmitting && (showModal = false)"
                >
                    Cancel
                </button>
                <button
                    class="ml-2 flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50"
                    :disabled="isSubmitting"
                    @click="confirmAction"
                >
                    <svg
                        v-if="isSubmitting"
                        class="animate-spin w-5 h-5 mr-2"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"
                        ></path>
                    </svg>
                    <span>{{
                        isSubmitting ? "Submitting..." : "Confirm"
                    }}</span>
                </button>
            </template>
        </ConfirmationModal>

        <ResponseModal
            :show="responseModal.show"
            :title="responseModal.title"
            :message="responseModal.message"
            :type="responseModal.type"
            @close="responseModal.show = false"
        />
    </div>

    <!-- Pagination -->
    <div v-if="data.links.length" class="flex justify-center mt-4 space-x-2">
        <button
            v-for="link in data.links"
            :key="link.label"
            @click="handlePageChange(link.url)"
            class="px-4 py-2 text-sm font-medium border rounded transition-colors"
            :class="{
                'border-[var(--primary-color)]': link.active,
                'bg-gray-300 text-gray-700 hover:bg-[var(--primary-color)] hover:border-[var(--primary-color)]':
                    !link.active && link.url,
                'opacity-50': !link.url,
            }"
            :style="{
                '--primary-color': buttonPrimaryBgColor,
                backgroundColor: link.active ? buttonPrimaryBgColor : undefined,
                color: link.active ? buttonPrimaryTextColor : undefined,
                '--hover-text-color': buttonPrimaryTextColor,
            }"
            :disabled="!link.url"
            v-html="link.label"
        ></button>
    </div>
</template>

<style scoped>
button:not(:disabled):hover {
    color: var(--hover-text-color);
}
</style>
