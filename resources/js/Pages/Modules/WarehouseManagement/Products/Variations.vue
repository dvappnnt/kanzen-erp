<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";

const modelName = "products";
const page = usePage();
const toast = useToast();
const isSubmitting = ref(false);
const showModal = ref(false);
const modalType = ref(""); // 'add', 'edit', 'delete'
const currentVariation = ref(null);
const showTechnicalDetails = ref({});

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const getQrUrl = (id) => {
    return route("qr.products", { product: usePage().props.modelData.id });
};

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    {
        label: "Category",
        value: (row) => row.category.name,
        class: "text-gray-600 font-semibold",
    },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.token),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read suppliers",
    },
    {
        text: "Images Information",
        url: `/${modelName}/${usePage().props.modelData.id}/images`,
        inertia: true,
        permission: "read product images",
    },
    {
        text: "Specifications Information",
        url: `/${modelName}/${usePage().props.modelData.id}/specifications`,
        inertia: true,
        permission: "read product specifications",
    },
    {
        text: "Variations Information",
        url: `/${modelName}/${usePage().props.modelData.id}/variations`,
        inertia: true,
        permission: "read product variations",
    },
]);

const modelData = computed(() => page.props.modelData || {});
const variations = ref(page.props.variations || []);
const availableAttributes = ref([]);
const selectedRows = ref([{ attributeId: "", valueId: "" }]);
const attributeValues = ref({}); // Store values for each attribute

// Add to the data section
const formData = ref({
    name: "Product Variation",
    is_default: false,
    sku: "",
    barcode: "",
});

// Load available attributes
onMounted(async () => {
    try {
        const { data } = await axios.get("/api/attributes");
        availableAttributes.value = data;
    } catch (error) {
        console.error("Error loading attributes:", error);
        toast.error("Failed to load attributes");
    }
});

// Load values for a specific attribute
const loadAttributeValues = async (attributeId) => {
    try {
        const { data } = await axios.get(
            `/api/attribute-values?attribute_id=${attributeId}`
        );
        attributeValues.value[attributeId] = data;
    } catch (error) {
        console.error("Error loading attribute values:", error);
        toast.error("Failed to load attribute values");
    }
};

const addNewRow = () => {
    selectedRows.value.push({ attributeId: "", valueId: "" });
};

const removeRow = (index) => {
    selectedRows.value.splice(index, 1);
};

// Add new refs for the new attribute/value modals
const showNewAttributeModal = ref(false);
const showNewValueModal = ref(false);
const newAttributeForm = ref({
    name: "",
    currentRowIndex: null,
});
const newValueForm = ref({
    value: "",
    currentRowIndex: null,
    attributeId: null,
});

// Add functions to handle new attribute/value creation
const openNewAttributeModal = (index) => {
    newAttributeForm.value.currentRowIndex = index;
    newAttributeForm.value.name = "";
    showNewAttributeModal.value = true;
};

const openNewValueModal = (index, attributeId) => {
    newValueForm.value = {
        currentRowIndex: index,
        attributeId: attributeId,
        value: "",
    };
    showNewValueModal.value = true;
};

const handleNewAttributeSubmit = async () => {
    try {
        const { data } = await axios.post("/api/attributes", {
            name: newAttributeForm.value.name,
        });

        // Add to available attributes
        availableAttributes.value.push(data.modelData);

        // Select the new attribute
        const index = newAttributeForm.value.currentRowIndex;
        selectedRows.value[index].attributeId = data.modelData.id;

        showNewAttributeModal.value = false;
        toast.success(data.message || "Attribute created successfully");

        // Load the values for the new attribute
        await loadAttributeValues(data.modelData.id);
    } catch (error) {
        console.error("Error creating attribute:", error);
        toast.error(
            error.response?.data?.message || "Failed to create attribute"
        );
    }
};

const handleNewValueSubmit = async () => {
    try {
        const { data } = await axios.post("/api/attribute-values", {
            attribute_id: parseInt(newValueForm.value.attributeId),
            value: newValueForm.value.value,
        });

        // Add to attribute values
        const attributeId = newValueForm.value.attributeId;
        if (!attributeValues.value[attributeId]) {
            attributeValues.value[attributeId] = [];
        }
        attributeValues.value[attributeId].push(data.modelData);

        // Select the new value
        const index = newValueForm.value.currentRowIndex;
        selectedRows.value[index].valueId = data.modelData.id;

        showNewValueModal.value = false;
        toast.success(data.message || "Value created successfully");
    } catch (error) {
        console.error("Error creating value:", error);
        toast.error(error.response?.data?.message || "Failed to create value");
    }
};

const handleAttributeChange = async (index, attributeId) => {
    if (attributeId === "new") {
        openNewAttributeModal(index);
        selectedRows.value[index].attributeId = "";
        return;
    }

    selectedRows.value[index].valueId = "";
    if (attributeId && !attributeValues.value[attributeId]) {
        await loadAttributeValues(attributeId);
    }
};

const handleValueChange = (index, valueId) => {
    if (valueId === "new") {
        const attributeId = selectedRows.value[index].attributeId;
        openNewValueModal(index, attributeId);
        selectedRows.value[index].valueId = "";
        return;
    }
};

const openModal = (type, variation = null) => {
    modalType.value = type;
    currentVariation.value = variation;
    if (type === "edit" && variation) {
        formData.value = {
            name: variation.name,
            is_default: Boolean(variation.is_default),
            sku: variation.sku || "",
            barcode: variation.barcode || "",
        };
        selectedRows.value = variation.attributes.map((attr) => ({
            attributeId: attr.attribute_id.toString(),
            valueId: attr.value.id.toString(),
        }));
        // Load attribute values for each selected attribute
        selectedRows.value.forEach(async (row) => {
            if (row.attributeId) {
                await loadAttributeValues(row.attributeId);
            }
        });
    } else if (type === "add") {
        formData.value = {
            name: "Product Variation",
            is_default: false,
            sku: "",
            barcode: "",
        };
        selectedRows.value = [{ attributeId: "", valueId: "" }];
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    modalType.value = "";
    currentVariation.value = null;
    selectedRows.value = [{ attributeId: "", valueId: "" }];
    formData.value = {
        name: "Product Variation",
        is_default: false,
        sku: "",
        barcode: "",
    };
};

const handleSubmit = async () => {
    try {
        isSubmitting.value = true;

        // Validate that all rows have both attribute and value selected
        const invalidRows = selectedRows.value.filter(
            (row) => !row.attributeId || !row.valueId
        );
        if (invalidRows.length > 0) {
            toast.error("Please select both attribute and value for all rows");
            return;
        }

        // Create variation with attributes in a single request
        const { data } = await axios.post(
            `/api/products/${modelData.value.id}/variations`,
            {
                name: formData.value.name,
                is_default: formData.value.is_default,
                sku: formData.value.sku,
                barcode: formData.value.barcode,
                attributes: selectedRows.value.map((row) => ({
                    attribute_id: parseInt(row.attributeId),
                    attribute_value_id: parseInt(row.valueId),
                })),
            }
        );

        toast.success("Variation added successfully!");
        closeModal();
        // Refresh the page to get updated data
        router.get(`/${modelName}/${modelData.value.id}/variations`);
    } catch (error) {
        console.error("Error saving variation:", error);
        toast.error(
            error.response?.data?.message || "Failed to save variation"
        );
    } finally {
        isSubmitting.value = false;
    }
};

const handleDelete = async () => {
    try {
        isSubmitting.value = true;
        await axios.delete(
            `/api/products/${modelData.value.id}/variations/${currentVariation.value.id}`
        );
        toast.success("Variation deleted successfully!");
        closeModal();
        // Refresh the page to get updated data
        router.get(`/${modelName}/${modelData.value.id}/variations`);
    } catch (error) {
        console.error("Error deleting variation:", error);
        toast.error("Failed to delete variation");
    } finally {
        isSubmitting.value = false;
    }
};

const handleEdit = async () => {
    try {
        isSubmitting.value = true;

        // Validate that all rows have both attribute and value selected
        const invalidRows = selectedRows.value.filter(
            (row) => !row.attributeId || !row.valueId
        );
        if (invalidRows.length > 0) {
            toast.error("Please select both attribute and value for all rows");
            return;
        }

        // Update variation with attributes
        const { data } = await axios.put(
            `/api/products/${modelData.value.id}/variations/${currentVariation.value.id}`,
            {
                name: formData.value.name,
                is_default: formData.value.is_default,
                sku: formData.value.sku,
                barcode: formData.value.barcode,
                attributes: selectedRows.value.map((row) => ({
                    attribute_id: parseInt(row.attributeId),
                    attribute_value_id: parseInt(row.valueId),
                })),
            }
        );

        toast.success("Variation updated successfully!");
        closeModal();
        // Refresh the page to get updated data
        router.get(`/${modelName}/${modelData.value.id}/variations`);
    } catch (error) {
        console.error("Error updating variation:", error);
        toast.error(
            error.response?.data?.message || "Failed to update variation"
        );
    } finally {
        isSubmitting.value = false;
    }
};

const toggleDetails = (variationId) => {
    showTechnicalDetails.value[variationId] =
        !showTechnicalDetails.value[variationId];
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

        <div class="max-w-4xl mx-auto">
            <NavigationTabs :tabs="navigationTabs" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />

                <div class="px-9 py-9">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Variations</h2>
                        <button
                            @click="openModal('add')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                            :class="{
                                'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                            }"
                            :style="{
                                '--primary-color': buttonPrimaryBgColor,
                                color: buttonPrimaryTextColor,
                            }"
                        >
                            Add Variation
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="variation in variations"
                            :key="variation.id"
                            class="p-4 border rounded-lg hover:shadow-md transition group bg-white"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-semibold">
                                        {{ variation.name }}
                                    </h3>
                                    <span
                                        v-if="variation.is_default"
                                        class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full"
                                    >
                                        Default
                                    </span>
                                </div>
                                <div
                                    class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="openModal('edit', variation)"
                                        class="text-gray-400 hover:text-blue-500 transition-colors"
                                        title="Edit"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openModal('delete', variation)"
                                        class="text-gray-400 hover:text-red-500 transition-colors"
                                        title="Delete"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="attr in variation.attributes"
                                        :key="attr.id"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-800"
                                    >
                                        {{ attr.attribute.name }}:
                                        {{ attr.value.value }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium">SKU:</span>
                                        <span class="ml-1">{{ variation.sku || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Barcode:</span>
                                        <span class="ml-1">{{ variation.barcode || '-' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <button
                                        @click="toggleDetails(variation.id)"
                                        class="text-xs text-gray-500 hover:text-gray-700 flex items-center space-x-1"
                                    >
                                        <span
                                            >{{
                                                showTechnicalDetails[
                                                    variation.id
                                                ]
                                                    ? "Hide"
                                                    : "Show"
                                            }}
                                            Technical Details</span
                                        >
                                        <svg
                                            class="w-4 h-4 transition-transform"
                                            :class="{
                                                'rotate-180':
                                                    showTechnicalDetails[
                                                        variation.id
                                                    ],
                                            }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    v-if="showTechnicalDetails[variation.id]"
                                    class="mt-3 bg-gray-50 rounded-md p-4 font-mono text-xs"
                                >
                                    <div
                                        v-for="attr in variation.attributes"
                                        :key="attr.id"
                                        class="mb-4 last:mb-0"
                                    >
                                        <div
                                            class="text-blue-600 font-semibold mb-1"
                                        >
                                            // {{ attr.attribute.name }} Details
                                        </div>
                                        <div class="pl-4 text-gray-700">
                                            <div
                                                class="grid grid-cols-[120px_1fr] gap-2"
                                            >
                                                <span class="text-purple-600"
                                                    >id:</span
                                                >
                                                <span>{{ attr.id }}</span>

                                                <span class="text-purple-600"
                                                    >attribute_id:</span
                                                >
                                                <span>{{
                                                    attr.attribute_id
                                                }}</span>

                                                <span class="text-purple-600"
                                                    >value_id:</span
                                                >
                                                <span>{{
                                                    attr.attribute_value_id
                                                }}</span>

                                                <span class="text-purple-600"
                                                    >created_at:</span
                                                >
                                                <span>{{
                                                    moment(
                                                        attr.created_at
                                                    ).format(
                                                        "YYYY-MM-DD HH:mm:ss"
                                                    )
                                                }}</span>

                                                <span class="text-purple-600"
                                                    >updated_at:</span
                                                >
                                                <span>{{
                                                    moment(
                                                        attr.updated_at
                                                    ).format(
                                                        "YYYY-MM-DD HH:mm:ss"
                                                    )
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{
                        modalType === "add"
                            ? "Add Variation"
                            : modalType === "edit"
                            ? "Edit Variation"
                            : "Delete Variation"
                    }}
                </h2>

                <div v-if="modalType !== 'delete'" class="space-y-4">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="flex-1">
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Name
                            </label>
                            <input
                                type="text"
                                v-model="formData.name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                SKU (Optional)
                            </label>
                            <input
                                type="text"
                                v-model="formData.sku"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Barcode (Optional)
                            </label>
                            <input
                                type="text"
                                v-model="formData.barcode"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                v-model="formData.is_default"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-600"
                                >Set as Default</span
                            >
                        </label>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="flex justify-end mb-4">
                            <button
                                @click="addNewRow"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                                :class="{
                                    'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                                }"
                                :style="{
                                    '--primary-color': buttonPrimaryBgColor,
                                    color: buttonPrimaryTextColor,
                                }"
                                type="button"
                            >
                                Add New
                            </button>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Attribute
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Value
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(row, index) in selectedRows"
                                    :key="index"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select
                                            v-model="row.attributeId"
                                            @change="
                                                handleAttributeChange(
                                                    index,
                                                    row.attributeId
                                                )
                                            "
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            required
                                        >
                                            <option value="">
                                                Select Attribute
                                            </option>
                                            <option
                                                v-for="attr in availableAttributes"
                                                :key="attr.id"
                                                :value="attr.id"
                                            >
                                                {{ attr.name }}
                                            </option>
                                            <option
                                                value="new"
                                                class="font-medium text-indigo-600"
                                            >
                                                + Add New Attribute
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select
                                            v-model="row.valueId"
                                            @change="
                                                handleValueChange(
                                                    index,
                                                    row.valueId
                                                )
                                            "
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            required
                                            :disabled="
                                                !row.attributeId ||
                                                row.attributeId === 'new'
                                            "
                                        >
                                            <option value="">
                                                Select Value
                                            </option>
                                            <option
                                                v-for="value in attributeValues[
                                                    row.attributeId
                                                ] || []"
                                                :key="value.id"
                                                :value="value.id"
                                            >
                                                {{ value.value }}
                                            </option>
                                            <option
                                                v-if="
                                                    row.attributeId &&
                                                    row.attributeId !== 'new'
                                                "
                                                value="new"
                                                class="font-medium text-indigo-600"
                                            >
                                                + Add New Value
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            v-if="selectedRows.length > 1"
                                            @click="removeRow(index)"
                                            class="text-red-600 hover:text-red-900"
                                            type="button"
                                        >
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">
                    Are you sure you want to delete this variation? This action
                    cannot be undone.
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeModal"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="
                            modalType === 'delete'
                                ? handleDelete()
                                : modalType === 'edit'
                                ? handleEdit()
                                : handleSubmit()
                        "
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                        :class="{
                            'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                        }"
                        :style="{
                            '--primary-color': buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                        :disabled="isSubmitting"
                    >
                        <span v-if="isSubmitting" class="animate-spin mr-2">
                            <svg
                                class="w-4 h-4"
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
                        </span>
                        {{
                            modalType === "delete"
                                ? "Delete"
                                : modalType === "edit"
                                ? "Update"
                                : "Save"
                        }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- New Attribute Modal -->
        <Modal
            :show="showNewAttributeModal"
            @close="showNewAttributeModal = false"
        >
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Add New Attribute
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input
                            type="text"
                            v-model="newAttributeForm.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showNewAttributeModal = false"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleNewAttributeSubmit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- New Value Modal -->
        <Modal :show="showNewValueModal" @close="showNewValueModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Add New Value
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Value
                        </label>
                        <input
                            type="text"
                            v-model="newValueForm.value"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showNewValueModal = false"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleNewValueSubmit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
