<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import {
    formatDate,
    humanReadable,
    getStatusPillClass,
    formatNumber,
    singularizeAndFormat,
} from "@/utils/global";
import Modal from "@/Components/Modal.vue";
import DetailedProfileCard from "@/Components/Sections/DetailedProfileCard.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import { useColors } from "@/Composables/useColors";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

const modelName = "warehouse-stock-transfers";
const page = usePage();
const toast = useToast();

const props = defineProps({
    modelData: {
        type: Object,
        required: true,
    },
});

// Base data
const modelData = computed(() => page.props.modelData);
const originWarehouseId = computed(() => modelData.value?.origin_warehouse_id);
const details = ref([]);

// Watch for modelData changes
watch(
    modelData,
    (newVal) => {
        if (newVal?.details && Array.isArray(newVal.details)) {
            details.value = newVal.details.map((d) => ({
                ...d,
                product:
                    d.origin_warehouse_product?.supplier_product_detail
                        ?.product || {},
                transferred_qty: d.transferred_qty || 0,
                serials: d.serials || [],
            }));
        }
    },
    { immediate: true }
);
const items = ref([]);
const supplierProducts = ref([]);

// Loading states
const isLoading = ref(false);
const isLoadingProducts = ref(false);
const hasLoadedProducts = ref(false);

// Modal states
const showSerialModal = ref(false);
const showEditModal = ref(false);
const showRemarksModal = ref(false);
const showActionRemarksModal = ref(false);
const showConfirmModal = ref(false);
const showReceiveModal = ref(false);
const showReturnModal = ref(false);
const showActionModal = ref(false);

// Form and input states
const serialModalRowIdx = ref(null);
const serialInput = ref("");
const serialsInputList = ref([]);
const serialValidationLoading = ref(false);
const serialValidationError = ref("");
const selectedProduct = ref(null);
const itemForm = ref({});
const purchaseOrderDetails = ref([]);
const editingDetail = ref(null);
const actionType = ref("");
const remarks = ref("");
const confirmAction = ref("");
const confirmMessage = ref("");
const approvalLevels = ref([]);
const approvalRemarks = ref([]);
const selectedRemarks = ref([]);
const selectedDetail = ref(null);
const actionNotes = ref("");

// Form objects
const submitting = ref(false);
const error = ref("");
const flash = (message, type = "success") => {
    const event = new CustomEvent("flash", {
        detail: { message, type },
    });
    window.dispatchEvent(event);
};

const receiveForm = ref({
    received_qty: 0,
    serials: [],
});

const returnForm = ref({
    return_qty: 0,
    serials: [],
    remarks: "",
});

const bulkManufacturedDate = ref("");
const bulkExpiryDate = ref("");

// Profile details configuration
const profileDetails = computed(() => [
    {
        label: "Transfer Number",
        value: (row) => row.number,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Transfer Date",
        value: (row) =>
            row.transfer_date ? formatDate("M d Y", row.transfer_date) : "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Status",
        value: (row) => humanReadable(row.status),
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Created By",
        value: (row) => row.created_by_user?.name || "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Created At",
        value: (row) =>
            row.created_at ? formatDate("M d Y", row.created_at) : "—",
        class: "text-gray-600 font-semibold",
    },
]);

// Computed properties
const isFullyReceived = computed(() => {
    return (
        modelData?.details?.every(
            (detail) => detail.expected_qty === detail.transferred_qty
        ) || false
    );
});

const canReceive = computed(() => modelData?.status === "approved");

const canReturn = computed(() => {
    return ["approved", "partially_received", "fully_received"].includes(
        props.modelData.status
    );
});

const canComplete = computed(() => {
    return props.modelData.status === "fully_received";
});

const canAddDetails = computed(
    () => modelData?.status === "pending" && details.value.length > 0
);

const actionTitle = computed(() => {
    const action =
        actionType.value.charAt(0).toUpperCase() + actionType.value.slice(1);
    return `${action} Stock Transfer`;
});

// Modal handlers
const openReceiveModal = (detail) => {
    selectedDetail.value = detail;
    receiveForm.value = {
        received_qty: 0,
        serials: [],
    };
    serialInput.value = "";
    serialValidationError.value = "";
    error.value = "";
    submitting.value = false;
    showReceiveModal.value = true;
};

const closeReceiveModal = () => {
    showReceiveModal.value = false;
    selectedDetail.value = null;
    receiveForm.value = {
        received_qty: 0,
        has_serials: false,
        serials: [],
        remarks: "",
    };
    serialInput.value = "";
    serialValidationError.value = "";
};

const openReturnModal = (detail) => {
    selectedDetail.value = detail;
    returnForm.value = {
        return_qty: 0,
        serials: [],
        remarks: "",
    };
    serialInput.value = "";
    serialValidationError.value = "";
    error.value = "";
    submitting.value = false;
    showReturnModal.value = true;
};

const closeReturnModal = () => {
    showReturnModal.value = false;
    selectedDetail.value = null;
    returnForm.value = {
        return_qty: 0,
        serials: [],
        remarks: "",
    };
};

const openActionRemarksModal = (type) => {
    actionType.value = type;
    remarks.value = "";
    showActionRemarksModal.value = true;
};

const closeActionRemarksModal = () => {
    showActionRemarksModal.value = false;
    actionType.value = "";
    remarks.value = "";
};

const openActionModal = (type) => {
    actionType.value = type;
    actionNotes.value = "";
    showActionModal.value = true;
};

const closeActionModal = () => {
    showActionModal.value = false;
    actionType.value = "";
    actionNotes.value = "";
};

// Serial handling
const validateSerial = async (serial) => {
    if (!serial || !selectedDetail.value) return;

    try {
        serialValidationLoading.value = true;
        serialValidationError.value = "";

        const response = await axios.get(
            "/api/warehouse-stock-transfers/validate-serial",
            {
                params: {
                    warehouse_stock_transfer_id: props.modelData.id,
                    warehouse_product_id:
                        selectedDetail.value.origin_warehouse_product_id,
                    serial_number: serial,
                },
            }
        );

        if (response.data.valid) {
            if (
                receiveForm.value.serials.some(
                    (s) => s.serial_number === serial
                )
            ) {
                serialValidationError.value = "Serial number already scanned";
            } else {
                receiveForm.value.serials.push({
                    serial_number: serial,
                    batch_number: response.data.data.batch_number,
                    manufactured_at: response.data.data.manufactured_at,
                    expired_at: response.data.data.expired_at,
                });
                serialInput.value = "";
                // Update received_qty based on serials count
                receiveForm.value.received_qty =
                    receiveForm.value.serials.length;
            }
        } else {
            serialValidationError.value = response.data.message;
        }
    } catch (error) {
        console.error("Serial validation error:", error);
        serialValidationError.value =
            error.response?.data?.message || "Failed to validate serial";
    } finally {
        serialValidationLoading.value = false;
    }
};

const removeSerial = (index) => {
    receiveForm.value.serials.splice(index, 1);
};

// Action handlers
const handleReceive = async () => {
    if (isLoading.value || !selectedDetail.value) return;

    if (
        receiveForm.value.has_serials &&
        receiveForm.value.serials.length !== receiveForm.value.received_qty
    ) {
        toast.error("Please scan all required serial numbers");
        return;
    }

    try {
        isLoading.value = true;
        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/receive`,
            receiveForm.value
        );
        toast.success("Items received successfully");
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to receive items");
    } finally {
        isLoading.value = false;
        closeReceiveModal();
    }
};

const handleReturn = async () => {
    if (isLoading.value || !selectedDetail.value) return;

    if (
        selectedDetail.value.origin_warehouse_product?.has_serials &&
        returnForm.value.serials.length !== returnForm.value.return_qty
    ) {
        toast.error("Please scan all serial numbers to return");
        return;
    }

    try {
        isLoading.value = true;
        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/return`,
            returnForm.value
        );
        toast.success("Items returned successfully");
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to return items");
    } finally {
        isLoading.value = false;
        closeReturnModal();
    }
};

const handleAction = async () => {
    if (!modelData.value?.id) return;

    try {
        isLoading.value = true;
        const response = await axios.post(
            `/api/warehouse-stock-transfers/${modelData.value.id}/${actionType.value}`,
            {
                notes: actionNotes.value,
            }
        );

        toast.success(response.data.message);
        closeActionModal();
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "An error occurred");
    } finally {
        isLoading.value = false;
    }
};

const handleComplete = async () => {
    try {
        submitting.value = true;
        error.value = "";

        await axios.post(
            `/api/warehouse-stock-transfers/${props.modelData.id}/complete`
        );

        // Update local status
        props.modelData.status = "completed";

        flash("Transfer completed successfully", "success");
    } catch (e) {
        error.value =
            e.response?.data?.message || "Failed to complete transfer";
    } finally {
        submitting.value = false;
    }
};

// Bulk date handling
const applyBulkDates = () => {
    receiveForm.value.serials = receiveForm.value.serials.map((serial) => ({
        ...serial,
        manufactured_at: bulkManufacturedDate.value || serial.manufactured_at,
        expired_at: bulkExpiryDate.value || serial.expired_at,
    }));
};

const addSerialRow = () => {
    if (receiveForm.value.serials.length < receiveForm.value.received_qty) {
        receiveForm.value.serials.push({
            serial_number: "",
            batch_number: "",
            manufactured_at: bulkManufacturedDate.value || "",
            expired_at: bulkExpiryDate.value || "",
        });
    }
};

const removeSerialRow = (idx) => {
    receiveForm.value.serials.splice(idx, 1);
};

const handleSerialScan = async (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        const serial = serialInput.value.trim();
        if (!serial) return;

        if (
            receiveForm.value.serials.length >=
            selectedDetail.value.expected_qty -
                selectedDetail.value.transferred_qty
        ) {
            serialValidationError.value =
                "All required serials have been scanned";
            return;
        }

        await validateSerialField(serial);
    }
};

const isAllSerialsValidated = computed(() => {
    if (!selectedDetail.value?.origin_warehouse_product?.has_serials)
        return false;
    const requiredCount =
        selectedDetail.value.expected_qty -
        selectedDetail.value.transferred_qty;
    return (
        receiveForm.value.serials.length === requiredCount &&
        receiveForm.value.serials.every((s) => s.validated)
    );
});

const canSubmitReceive = computed(() => {
    if (!selectedDetail.value) return false;

    if (selectedDetail.value.origin_warehouse_product?.has_serials) {
        return (
            receiveForm.value.serials.length > 0 &&
            receiveForm.value.serials.every((s) => s.validated) &&
            receiveForm.value.serials.length <=
                selectedDetail.value.expected_qty -
                    selectedDetail.value.transferred_qty
        );
    }

    return (
        receiveForm.value.received_qty > 0 &&
        receiveForm.value.received_qty <=
            selectedDetail.value.expected_qty -
                selectedDetail.value.transferred_qty
    );
});

const validateSerialField = async (serial) => {
    if (!serial) return;

    try {
        serialValidationLoading.value = true;
        serialValidationError.value = "";

        const response = await axios.get(
            "/api/warehouse-stock-transfers/validate-serial",
            {
                params: {
                    warehouse_stock_transfer_id: props.modelData.id,
                    warehouse_product_id:
                        selectedDetail.value.origin_warehouse_product_id,
                    serial_number: serial,
                },
            }
        );

        if (response.data.valid) {
            // Check if serial is already in the list
            const existingIndex = receiveForm.value.serials.findIndex(
                (s) => s.serial_number === serial
            );

            if (existingIndex !== -1) {
                serialValidationError.value = "Serial number already scanned";
            } else {
                receiveForm.value.serials.push({
                    serial_number: serial,
                    batch_number: response.data.data.batch_number,
                    manufactured_at: response.data.data.manufactured_at,
                    expired_at: response.data.data.expired_at,
                    validated: true,
                });
                serialInput.value = "";
            }
        } else {
            serialValidationError.value = response.data.message;
        }
    } catch (error) {
        serialValidationError.value =
            error.response?.data?.message || "Failed to validate serial";
    } finally {
        serialValidationLoading.value = false;
    }
};

onMounted(() => {
    if (modelData.value?.details && Array.isArray(modelData.value.details)) {
        details.value = modelData.value.details.map((d) => ({
            ...d,
            product:
                d.origin_warehouse_product?.supplier_product_detail?.product ||
                {},
            transferred_qty: d.transferred_qty || 0,
            serials: d.serials || [],
        }));
    }
});

// Watch for changes in return_qty to validate against max allowed
watch(
    () => returnForm.value.return_qty,
    (newVal) => {
        if (!selectedDetail.value) return;

        if (newVal > selectedDetail.value.transferred_qty) {
            returnForm.value.return_qty = selectedDetail.value.transferred_qty;
        }
    }
);

// Update the submit method to set received_qty based on serials
const submitReceiveForm = async () => {
    try {
        submitting.value = true;
        error.value = "";

        // For serialized items, set received_qty to number of serials
        if (selectedDetail.value.origin_warehouse_product?.has_serials) {
            receiveForm.value.received_qty = receiveForm.value.serials.length;
        }

        // Validate received quantity
        if (
            !receiveForm.value.received_qty ||
            receiveForm.value.received_qty < 1
        ) {
            if (selectedDetail.value.origin_warehouse_product?.has_serials) {
                error.value = "Please scan at least one serial number";
            } else {
                error.value = "Please enter a valid quantity";
            }
            return;
        }

        // Validate max quantity
        const remainingQty =
            selectedDetail.value.expected_qty -
            selectedDetail.value.transferred_qty;
        if (receiveForm.value.received_qty > remainingQty) {
            error.value = `Cannot receive more than ${remainingQty} items`;
            return;
        }

        // For serialized items, validate that we have the correct number of serials
        if (
            selectedDetail.value.origin_warehouse_product?.has_serials &&
            receiveForm.value.serials.length !== receiveForm.value.received_qty
        ) {
            error.value = `Number of scanned serials must match the quantity to receive`;
            return;
        }

        // Add origin warehouse product info to the payload
        const originProduct = selectedDetail.value.origin_warehouse_product;
        receiveForm.value.origin_warehouse_product = {
            supplier_product_detail_id:
                originProduct.supplier_product_detail_id,
            sku: originProduct.sku,
            barcode: originProduct.barcode,
            critical_level_qty: originProduct.critical_level_qty,
            price: originProduct.price,
            last_cost: originProduct.last_cost,
            average_cost: originProduct.average_cost,
            has_serials: originProduct.has_serials,
        };

        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/receive`,
            receiveForm.value
        );

        // Refresh the data
        const detail = props.modelData.details.find(
            (d) => d.id === selectedDetail.value.id
        );
        if (detail) {
            detail.transferred_qty =
                detail.transferred_qty + receiveForm.value.received_qty;
        }

        showReceiveModal.value = false;
        selectedDetail.value = null;

        flash("Items received successfully", "success");
    } catch (e) {
        error.value = e.response?.data?.message || "Failed to receive items";
    } finally {
        submitting.value = false;
    }
};

// Add return submit handler
const submitReturnForm = async () => {
    try {
        submitting.value = true;
        error.value = "";

        // For serialized items, set return_qty to number of serials
        if (selectedDetail.value.origin_warehouse_product?.has_serials) {
            returnForm.value.return_qty = returnForm.value.serials.length;
        }

        // Validate return quantity
        if (!returnForm.value.return_qty || returnForm.value.return_qty < 1) {
            if (selectedDetail.value.origin_warehouse_product?.has_serials) {
                error.value = "Please scan at least one serial number";
            } else {
                error.value = "Please enter a valid quantity";
            }
            return;
        }

        // Validate max quantity (can't return more than transferred)
        if (
            returnForm.value.return_qty > selectedDetail.value.transferred_qty
        ) {
            error.value = `Cannot return more than ${selectedDetail.value.transferred_qty} items`;
            return;
        }

        // For serialized items, validate that we have the correct number of serials
        if (
            selectedDetail.value.origin_warehouse_product?.has_serials &&
            returnForm.value.serials.length !== returnForm.value.return_qty
        ) {
            error.value = `Number of scanned serials must match the quantity to return`;
            return;
        }

        // Add destination warehouse product info to the payload
        const destinationProduct =
            selectedDetail.value.destination_warehouse_product;
        returnForm.value.destination_warehouse_product = {
            supplier_product_detail_id:
                destinationProduct.supplier_product_detail_id,
            sku: destinationProduct.sku,
            barcode: destinationProduct.barcode,
            critical_level_qty: destinationProduct.critical_level_qty,
            price: destinationProduct.price,
            last_cost: destinationProduct.last_cost,
            average_cost: destinationProduct.average_cost,
            has_serials: destinationProduct.has_serials,
        };

        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/return`,
            returnForm.value
        );

        // Update the local data
        const detail = props.modelData.details.find(
            (d) => d.id === selectedDetail.value.id
        );
        if (detail) {
            detail.transferred_qty =
                detail.transferred_qty - returnForm.value.return_qty;
        }

        showReturnModal.value = false;
        selectedDetail.value = null;

        flash("Items returned successfully", "success");
    } catch (e) {
        error.value = e.response?.data?.message || "Failed to return items";
    } finally {
        submitting.value = false;
    }
};

// Add computed property for return form validation
const canSubmitReturn = computed(() => {
    if (!selectedDetail.value) return false;

    if (selectedDetail.value.origin_warehouse_product?.has_serials) {
        return (
            returnForm.value.serials.length > 0 &&
            returnForm.value.serials.every((s) => s.validated) &&
            returnForm.value.serials.length <=
                selectedDetail.value.transferred_qty
        );
    }

    return (
        returnForm.value.return_qty > 0 &&
        returnForm.value.return_qty <= selectedDetail.value.transferred_qty
    );
});
</script>

<template>
    <AppLayout :title="singularizeAndFormat(modelName) + ' Details'">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <div class="flex items-center space-x-2">
                    <button
                        v-if="modelData?.status === 'pending'"
                        @click="() => openActionModal('approve')"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Approve
                    </button>
                    <button
                        v-if="modelData?.status === 'pending'"
                        @click="() => openActionModal('reject')"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Reject
                    </button>
                    <button
                        v-if="modelData?.status === 'pending'"
                        @click="() => openActionModal('cancel')"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Cancel
                    </button>
                    <button
                        v-if="modelData?.status === 'fully-transferred'"
                        @click="handleComplete"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Complete
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Main Details Card -->
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6"
                >
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    NUMBER
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{ modelData?.number }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    COMPANY
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{
                                        modelData?.origin_warehouse?.company
                                            ?.name
                                    }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    ORIGIN WAREHOUSE
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{ modelData?.origin_warehouse?.name }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    DESTINATION WAREHOUSE
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{ modelData?.destination_warehouse?.name }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    STATUS
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold"
                                    :class="{
                                        'text-yellow-600':
                                            modelData?.status === 'pending',
                                        'text-green-600':
                                            modelData?.status === 'approved',
                                        'text-red-600':
                                            modelData?.status === 'rejected',
                                        'text-gray-600':
                                            modelData?.status === 'cancelled',
                                    }"
                                >
                                    {{ humanReadable(modelData?.status) }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">
                                    TRANSFER DATE
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{
                                        modelData?.transfer_date
                                            ? formatDate(
                                                  "M d Y",
                                                  modelData.transfer_date
                                              )
                                            : "—"
                                    }}
                                </p>
                            </div>
                            <div v-if="modelData?.remarks">
                                <h3 class="text-sm font-medium text-gray-500">
                                    REMARKS
                                </h3>
                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{ modelData.remarks }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Transfer Details Table -->
                    <div
                        v-if="modelData?.details?.length > 0"
                        class="bg-white overflow-hidden shadow-xl sm:rounded-lg"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Product
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        QTY
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Serials
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="detail in modelData?.details"
                                    :key="detail.id"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{
                                                detail.origin_warehouse_product
                                                    ?.supplier_product_detail
                                                    ?.product?.name
                                            }}
                                        </div>
                                        <div class="text-sm text-gray-900">
                                            {{
                                                detail.origin_warehouse_product
                                                    ?.sku
                                            }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{
                                                detail.origin_warehouse_product
                                                    ?.barcode
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ detail.transferred_qty || 0 }} /
                                            {{ detail.expected_qty }}
                                            <span
                                                v-if="
                                                    detail.transferred_qty > 0
                                                "
                                                class="ml-1 text-xs text-green-600"
                                            >
                                                ({{
                                                    (
                                                        (detail.transferred_qty /
                                                            detail.expected_qty) *
                                                        100
                                                    ).toFixed(0)
                                                }}%)
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Available:
                                            {{
                                                detail.origin_warehouse_product
                                                    ?.qty
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            v-if="
                                                detail.serials &&
                                                detail.serials.length > 0
                                            "
                                            class="text-sm text-gray-900"
                                        >
                                            <div
                                                v-for="serial in detail.serials"
                                                :key="serial.id"
                                                class="flex items-center space-x-2"
                                            >
                                                <span>{{
                                                    serial.serial_number
                                                }}</span>
                                                <svg
                                                    class="h-4 w-4 text-green-500"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-sm text-gray-500"
                                        >
                                            No serials
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            <!-- Receive Button -->
                                            <button
                                                v-if="
                                                    modelData.status ===
                                                        'approved' &&
                                                    detail.transferred_qty <
                                                        detail.expected_qty
                                                "
                                                @click="
                                                    openReceiveModal(detail)
                                                "
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            >
                                                Receive
                                            </button>

                                            <!-- Return Button -->
                                            <button
                                                v-if="
                                                    canReturn &&
                                                    detail.transferred_qty > 0
                                                "
                                                @click="openReturnModal(detail)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Return
                                            </button>

                                            <!-- Status Display -->
                                            <span
                                                v-if="
                                                    detail.transferred_qty ===
                                                    detail.expected_qty
                                                "
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800"
                                            >
                                                Completed
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Remarks Modal -->
        <Modal :show="showActionRemarksModal" @close="closeActionRemarksModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ actionTitle }}
                </h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Remarks</label
                    >
                    <textarea
                        v-model="remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    ></textarea>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="closeActionRemarksModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="handleAction"
                        :disabled="isLoading"
                    >
                        {{ isLoading ? "Processing..." : "Confirm" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Receive Modal -->
        <DialogModal :show="showReceiveModal" @close="showReceiveModal = false">
            <template #title> Receive Items </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Non-serialized products -->
                    <div
                        v-if="
                            selectedDetail &&
                            !selectedDetail.origin_warehouse_product
                                ?.has_serials
                        "
                    >
                        <label class="block text-sm font-medium text-gray-700">
                            Quantity to Receive (Max:
                            {{
                                selectedDetail.expected_qty -
                                selectedDetail.transferred_qty
                            }})
                        </label>
                        <input
                            type="number"
                            v-model="receiveForm.received_qty"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            :max="
                                selectedDetail.expected_qty -
                                selectedDetail.transferred_qty
                            "
                            min="1"
                        />
                    </div>

                    <!-- Serialized products -->
                    <div
                        v-if="
                            selectedDetail?.origin_warehouse_product
                                ?.has_serials
                        "
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Serial Numbers</label
                            >
                            <span class="text-sm text-gray-500">
                                {{ receiveForm.serials.length }} of
                                {{
                                    selectedDetail.expected_qty -
                                    selectedDetail.transferred_qty
                                }}
                                required
                            </span>
                        </div>

                        <!-- Scanner input -->
                        <div class="mt-1">
                            <div class="flex space-x-2">
                                <div class="flex-1">
                                    <input
                                        type="text"
                                        v-model="serialInput"
                                        @keydown.enter.prevent="
                                            handleSerialScan
                                        "
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Scan or enter serial number"
                                        :disabled="
                                            receiveForm.serials.length >=
                                            selectedDetail.expected_qty -
                                                selectedDetail.transferred_qty
                                        "
                                    />
                                </div>
                            </div>
                            <p
                                v-if="serialValidationError"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ serialValidationError }}
                            </p>
                        </div>

                        <!-- Scanned Serials Table -->
                        <div v-if="receiveForm.serials.length > 0" class="mt-4">
                            <div
                                class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg"
                            >
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Serial Number
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Batch Number
                                            </th>
                                            <th
                                                scope="col"
                                                class="relative px-6 py-3"
                                            >
                                                <span class="sr-only"
                                                    >Actions</span
                                                >
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="(
                                                serial, index
                                            ) in receiveForm.serials"
                                            :key="index"
                                        >
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                            >
                                                {{ serial.serial_number }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                            >
                                                {{ serial.batch_number || "-" }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                            >
                                                <button
                                                    @click="
                                                        receiveForm.serials.splice(
                                                            index,
                                                            1
                                                        )
                                                    "
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex items-center justify-between">
                    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                    <div class="flex">
                        <SecondaryButton @click="showReceiveModal = false"
                            >Cancel</SecondaryButton
                        >
                        <PrimaryButton
                            class="ml-3"
                            @click="submitReceiveForm"
                            :disabled="!canSubmitReceive || submitting"
                            :loading="submitting"
                        >
                            Receive
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </DialogModal>

        <!-- Return Modal -->
        <DialogModal :show="showReturnModal" @close="showReturnModal = false">
            <template #title> Return Items </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Non-serialized products -->
                    <div
                        v-if="
                            selectedDetail &&
                            !selectedDetail.origin_warehouse_product
                                ?.has_serials
                        "
                    >
                        <label class="block text-sm font-medium text-gray-700">
                            Quantity to Return (Max:
                            {{ selectedDetail.transferred_qty }})
                        </label>
                        <input
                            type="number"
                            v-model="returnForm.return_qty"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            :max="selectedDetail.transferred_qty"
                            min="1"
                        />
                    </div>

                    <!-- Serialized products -->
                    <div
                        v-if="
                            selectedDetail?.origin_warehouse_product
                                ?.has_serials
                        "
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Serial Numbers</label
                            >
                            <span class="text-sm text-gray-500">
                                {{ returnForm.serials.length }} of
                                {{ selectedDetail.transferred_qty }} available
                            </span>
                        </div>

                        <!-- Scanner input -->
                        <div class="mt-1">
                            <div class="flex space-x-2">
                                <div class="flex-1">
                                    <input
                                        type="text"
                                        v-model="serialInput"
                                        @keydown.enter.prevent="
                                            handleSerialScan
                                        "
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Scan or enter serial number"
                                        :disabled="
                                            returnForm.serials.length >=
                                            selectedDetail.transferred_qty
                                        "
                                    />
                                </div>
                            </div>
                            <p
                                v-if="serialValidationError"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ serialValidationError }}
                            </p>
                        </div>

                        <!-- Scanned Serials Table -->
                        <div v-if="returnForm.serials.length > 0" class="mt-4">
                            <div
                                class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg"
                            >
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Serial Number
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Batch Number
                                            </th>
                                            <th
                                                scope="col"
                                                class="relative px-6 py-3"
                                            >
                                                <span class="sr-only"
                                                    >Actions</span
                                                >
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="(
                                                serial, index
                                            ) in returnForm.serials"
                                            :key="index"
                                        >
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                            >
                                                {{ serial.serial_number }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                            >
                                                {{ serial.batch_number || "-" }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                            >
                                                <button
                                                    @click="
                                                        returnForm.serials.splice(
                                                            index,
                                                            1
                                                        )
                                                    "
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Remarks
                        </label>
                        <textarea
                            v-model="returnForm.remarks"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Enter any remarks about the return"
                        ></textarea>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex items-center justify-between">
                    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                    <div class="flex">
                        <SecondaryButton @click="showReturnModal = false"
                            >Cancel</SecondaryButton
                        >
                        <PrimaryButton
                            class="ml-3"
                            @click="submitReturnForm"
                            :disabled="!canSubmitReturn || submitting"
                            :loading="submitting"
                        >
                            Return
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </DialogModal>

        <!-- Action Modal -->
        <Modal :show="showActionModal" @close="closeActionModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ actionTitle }}
                </h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Notes (Optional)</label
                    >
                    <textarea
                        v-model="actionNotes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        :placeholder="
                            'Add any notes about this ' +
                            actionType +
                            ' action...'
                        "
                    ></textarea>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="closeActionModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white"
                        :class="{
                            'bg-green-600 hover:bg-green-700 focus:ring-green-500':
                                actionType === 'approve',
                            'bg-red-600 hover:bg-red-700 focus:ring-red-500':
                                actionType === 'reject',
                            'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500':
                                actionType === 'cancel',
                        }"
                        @click="handleAction"
                        :disabled="isLoading"
                    >
                        {{
                            isLoading
                                ? "Processing..."
                                : "Confirm " +
                                  actionType.charAt(0).toUpperCase() +
                                  actionType.slice(1)
                        }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
