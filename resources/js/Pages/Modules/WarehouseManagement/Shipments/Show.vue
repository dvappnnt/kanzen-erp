<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import {
    singularizeAndFormat,
    formatNumber,
    humanReadable,
} from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

const modelName = "shipments";
const toast = useToast();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    { label: "Email", value: "email", class: "text-gray-500" },
    {
        label: "Role",
        value: (row) => row.role,
        class: "text-gray-600 font-semibold",
    },
];

const contactDetails = ref([
    { label: "Located At", value: "address" },
    { label: "Mobile", value: "mobile" },
    { label: "Landline", value: "landline" },
    { label: "Description", value: "description" },
    { label: "Website", value: "website" },
]);

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const details = computed(() => page.props.details || []);

const formatDate = (date) => {
    return date ? moment(date).format("MMMM D, YYYY") : "-";
};

// Selected items for bulk actions
const selectedItems = ref([]);
const selectAll = ref(false);
const couriers = ref([]);

// Toggle all checkboxes
const toggleAll = () => {
    if (selectAll.value) {
        selectedItems.value = details.value
            .filter((detail) => detail.status !== "delivered")
            .map((detail) => detail.id);
    } else {
        selectedItems.value = [];
    }
};

// Watch selected items to update selectAll state
const updateSelectAllState = () => {
    const availableDetails = details.value.filter(
        (detail) => detail.status !== "delivered"
    );
    selectAll.value = selectedItems.value.length === availableDetails.length;
};

// Modals state
const showTrackingModal = ref(false);
const isSubmitting = ref(false);
const selectedDetail = ref(null);
const modalType = ref(null);

// Form data
const deliveryForm = ref({
    courier_id: null,
    tracking_number: "",
    tracking_url: "",
    shipment_date: moment().format("YYYY-MM-DD"),
    delivered_date: moment().format("YYYY-MM-DD"),
    destination: "",
    notes: "",
});

// Fetch couriers
const fetchCouriers = async () => {
    try {
        const response = await axios.get("/api/complete/couriers");
        couriers.value = response.data;
    } catch (error) {
        console.error("Failed to fetch couriers:", error);
    }
};

onMounted(() => {
    fetchCouriers();
});

const modalTitle = computed(() => {
    switch (modalType.value) {
        case "for-pickup":
            return "Mark for Pickup";
        case "in-transit":
            return "Mark In Transit";
        case "delivered":
            return "Mark as Delivered";
        default:
            return "";
    }
});

// Reset form based on modal type
const resetForm = (type) => {
    modalType.value = type;
    deliveryForm.value = {
        courier_id: null,
        tracking_number: "",
        tracking_url: "",
        shipment_date: moment().format("YYYY-MM-DD"),
        delivered_date: moment().format("YYYY-MM-DD"),
        destination: "",
        notes: "",
    };

    // If shipping method is pickup, set destination to company address
    if (
        type === "for-pickup" &&
        modelData.value.invoice?.shipping_method === "pickup"
    ) {
        deliveryForm.value.destination = modelData.value.company?.address || "";
    }
};

const openActionModal = (type, detail = null) => {
    resetForm(type);
    selectedDetail.value = detail;
    if (detail) {
        deliveryForm.value = {
            ...deliveryForm.value,
            courier_id: detail.courier_id,
            tracking_number: detail.tracking_number,
            tracking_url: detail.tracking_url,
            shipment_date:
                detail.shipment_date || moment().format("YYYY-MM-DD"),
            delivered_date: moment().format("YYYY-MM-DD"),
            destination:
                detail.destination ||
                (modelData.value.invoice?.shipping_method === "pickup"
                    ? modelData.value.company?.address
                    : ""),
            notes: detail.notes || "",
        };
    }
    showTrackingModal.value = true;
};

const markForPickup = () => openActionModal("for-pickup");
const markInTransit = () => openActionModal("in-transit");
const markDelivered = () => openActionModal("delivered");

const handleSubmit = async () => {
    try {
        isSubmitting.value = true;

        // Get the items to update (either selected items for bulk or single selected detail)
        const itemsToUpdate =
            selectedItems.value.length > 0
                ? selectedItems.value
                : [selectedDetail.value.id];

        const promises = itemsToUpdate.map((detailId) =>
            axios.post(
                `/api/shipment-details/${detailId}/${modalType.value}`,
                deliveryForm.value
            )
        );

        await Promise.all(promises);
        toast.success(
            `Items marked as ${modalType.value.replace("-", " ")} successfully`
        );
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || `Failed to update status`);
    } finally {
        isSubmitting.value = false;
        showTrackingModal.value = false;
    }
};

// Computed property for bulk action availability
const canMarkForPickup = computed(() => {
    return (
        selectedItems.value.length > 0 &&
        selectedItems.value.every((id) => {
            const detail = details.value.find((d) => d.id === id);
            return detail.status === "pending";
        })
    );
});

const canMarkInTransit = computed(() => {
    return (
        selectedItems.value.length > 0 &&
        selectedItems.value.every((id) => {
            const detail = details.value.find((d) => d.id === id);
            return detail.status === "for-pickup";
        })
    );
});

const canMarkDelivered = computed(() => {
    return (
        selectedItems.value.length > 0 &&
        selectedItems.value.every((id) => {
            const detail = details.value.find((d) => d.id === id);
            return detail.status === "in-transit";
        })
    );
});

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${modelData.value.id}`,
        inertia: true,
        permission: "read suppliers",
    },
    {
        text: "Products Information",
        url: `/${modelName}/${modelData.value.id}/products`,
        inertia: true,
        permission: "read supplier products",
    },
]);
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

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8"
                >
                    <!-- Header Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <div class="flex justify-between">
                            <!-- Company Info -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">
                                    {{ modelData.company?.name }}
                                </h1>
                                <p class="text-gray-600">
                                    {{ modelData.company?.address }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.mobile }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.email }}
                                </p>
                            </div>
                            <!-- Shipment Info -->
                            <div class="text-right">
                                <h2
                                    class="text-xl font-bold text-gray-800 mb-2"
                                >
                                    SHIPMENT DETAILS
                                </h2>
                                <p class="text-gray-600">
                                    Shipment #:
                                    <span class="font-semibold">{{
                                        modelData.number
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Invoice #:
                                    <span class="font-semibold">{{
                                        modelData.invoice?.number
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Date:
                                    <span class="font-semibold">{{
                                        formatDate(modelData.created_at)
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Shipping Method:
                                    <span class="font-semibold capitalize">{{
                                        modelData.invoice?.shipping_method
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Status:
                                    <span class="font-semibold capitalize">{{
                                        humanReadable(modelData.status)
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info Section -->
                    <div
                        class="py-8 border-b border-gray-200 flex justify-between gap-8"
                    >
                        <!-- Left: Customer Information -->
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">
                                Customer Information:
                            </h3>
                            <p class="font-medium text-gray-800">
                                {{ modelData.invoice?.customer?.name }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.invoice?.customer?.address }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.invoice?.customer?.phone }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.invoice?.customer?.email }}
                            </p>
                        </div>

                        <!-- Right: Company Info if shipping_method is for-pickup -->
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">
                                Pickup Location:
                            </h3>
                            <p class="font-medium text-gray-800">
                                {{ modelData.company?.name }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.company?.address }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.company?.email }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.company?.mobile }}
                            </p>
                            <p class="text-gray-600">
                                {{ modelData.company?.landline }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">
                                Destination Location:
                            </h3>

                            <span
                                v-if="
                                    modelData.invoice?.shipping_method ===
                                    'pickup'
                                "
                            >
                                <p class="font-medium text-gray-800">
                                    {{ modelData.company?.name }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.address }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.email }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.mobile }}
                                </p>
                                <p class="text-gray-600">
                                    {{ modelData.company?.landline }}
                                </p>
                            </span>
                            <span v-else>
                                <p class="text-gray-600">
                                    Check the destination addresses below
                                </p>
                            </span>
                        </div>
                    </div>

                    <!-- Shipment Details Table -->
                    <div class="py-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Shipment Items
                            </h3>
                            <div class="flex gap-2">
                                <button
                                    v-if="canMarkForPickup"
                                    @click="markForPickup"
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm"
                                >
                                    Mark for Pickup
                                </button>
                                <button
                                    v-if="canMarkInTransit"
                                    @click="markInTransit"
                                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm"
                                >
                                    Mark In Transit
                                </button>
                                <button
                                    v-if="canMarkDelivered"
                                    @click="markDelivered"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm"
                                >
                                    Mark as Delivered
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 bg-gray-50">
                                            <input
                                                type="checkbox"
                                                v-model="selectAll"
                                                @change="toggleAll"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Tracking #
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Item
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Qty
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Destination
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                        <th
                                            v-if="modelData.status !== 'fully-delivered'"
                                            class="px-2 py-2 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="detail in details"
                                        :key="detail.id"
                                    >
                                        <td class="px-2 py-2">
                                            <input
                                                type="checkbox"
                                                v-model="selectedItems"
                                                :value="detail.id"
                                                :disabled="
                                                    detail.status ===
                                                    'delivered'
                                                "
                                                @change="updateSelectAllState"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <div
                                                class="font-semibold text-gray-900"
                                            >
                                                {{
                                                    detail.courier?.name || "-"
                                                }}
                                            </div>
                                            <a
                                                v-if="
                                                    detail.tracking_url &&
                                                    detail.tracking_number
                                                "
                                                :href="detail.tracking_url"
                                                target="_blank"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                {{ detail.tracking_number }}
                                            </a>
                                            <span v-else>{{
                                                detail.tracking_number || "-"
                                            }}</span>

                                            <p class="text-sm text-gray-500" v-if="detail.tracking_number">
                                                {{ detail.qty }} pc(s) to be
                                                delivered
                                            </p>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    detail.invoice_detail
                                                        ?.warehouse_product
                                                        ?.supplier_product_detail
                                                        ?.product?.name
                                                }}
                                            </div>
                                            <div
                                                v-if="
                                                    detail.invoice_detail
                                                        ?.warehouse_product
                                                        ?.supplier_product_detail
                                                        ?.product_variation
                                                "
                                                class="text-sm text-gray-500"
                                            >
                                                {{
                                                    detail.invoice_detail
                                                        .warehouse_product
                                                        .supplier_product_detail
                                                        .product_variation.name
                                                }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            {{ detail.qty }}
                                        </td>
                                        <td class="px-2 py-2">
                                            {{ detail.destination || "-" }}
                                            <p class="text-sm text-gray-500" v-if="detail.notes">
                                                Notes: {{ detail.notes || "-" }}
                                            </p>
                                        </td>
                                        <td class="px-2 py-2">
                                            <span
                                                :class="{
                                                    'px-2 py-1 text-xs rounded-full': true,
                                                    'bg-yellow-100 text-yellow-800':
                                                        detail.status ===
                                                        'pending',
                                                    'bg-blue-100 text-blue-800':
                                                        detail.status ===
                                                        'for-pickup',
                                                    'bg-orange-100 text-orange-800':
                                                        detail.status ===
                                                        'in-transit',
                                                    'bg-green-100 text-green-800':
                                                        detail.status ===
                                                        'delivered',
                                                }"
                                            >
                                                {{
                                                    humanReadable(detail.status)
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 text-center" v-if="modelData.status !== 'fully-delivered'">
                                            <div
                                                class="flex justify-center space-x-2"
                                            >
                                                <button
                                                    v-if="
                                                        detail.status ===
                                                        'pending'
                                                    "
                                                    @click="
                                                        openActionModal(
                                                            'for-pickup',
                                                            detail
                                                        )
                                                    "
                                                    class="text-yellow-600 hover:text-yellow-800"
                                                    title="Mark for Pickup"
                                                >
                                                    <i
                                                        class="fas fa-truck-loading"
                                                    ></i>
                                                </button>
                                                <button
                                                    v-if="
                                                        detail.status ===
                                                        'for-pickup'
                                                    "
                                                    @click="
                                                        openActionModal(
                                                            'in-transit',
                                                            detail
                                                        )
                                                    "
                                                    class="text-orange-600 hover:text-orange-800"
                                                    title="Mark In Transit"
                                                >
                                                    <i
                                                        class="fas fa-shipping-fast"
                                                    ></i>
                                                </button>
                                                <button
                                                    v-if="
                                                        detail.status ===
                                                        'in-transit'
                                                    "
                                                    @click="
                                                        openActionModal(
                                                            'delivered',
                                                            detail
                                                        )
                                                    "
                                                    class="text-green-600 hover:text-green-800"
                                                    title="Mark as Delivered"
                                                >
                                                    <i
                                                        class="fas fa-check-circle"
                                                    ></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Update Tracking Modal -->
    <div
        v-if="showTrackingModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full"
    >
        <div
            class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        >
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ modalTitle }}
                </h3>
                <button
                    @click="showTrackingModal = false"
                    class="text-gray-400 hover:text-gray-500"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="handleSubmit">
                <div class="space-y-4">
                    <!-- For Pickup Fields -->
                    <template v-if="modalType === 'for-pickup'">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Courier
                                <span class="text-red-500">*</span></label
                            >
                            <select
                                v-model="deliveryForm.courier_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select Courier</option>
                                <option
                                    v-for="courier in couriers"
                                    :key="courier.id"
                                    :value="courier.id"
                                >
                                    {{ courier.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Shipment Date
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="date"
                                v-model="deliveryForm.shipment_date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Destination Address
                                <span class="text-red-500">*</span></label
                            >
                            <textarea
                                v-model="deliveryForm.destination"
                                required
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Notes
                                <span class="text-gray-500"
                                    >(optional)</span
                                ></label
                            >
                            <textarea
                                v-model="deliveryForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </template>

                    <!-- In Transit Fields -->
                    <template v-if="modalType === 'in-transit'">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Tracking Number
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="text"
                                v-model="deliveryForm.tracking_number"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Tracking URL
                                <span class="text-gray-500"
                                    >(optional)</span
                                ></label
                            >
                            <input
                                type="url"
                                v-model="deliveryForm.tracking_url"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </template>

                    <!-- Delivered Fields -->
                    <template v-if="modalType === 'delivered'">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Delivered Date
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="date"
                                v-model="deliveryForm.delivered_date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </template>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="showTrackingModal = false"
                        class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        <span v-if="isSubmitting">Saving...</span>
                        <span v-else>Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
