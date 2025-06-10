<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import DetailedProfileCard from "@/Components/Sections/DetailedProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import {
    singularizeAndFormat,
    formatNumber,
    getStatusPillClass,
} from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import QRCode from "qrcode.vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { formatDate, humanReadable } from "@/utils/global";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "goods-receipts";
const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const details = ref(modelData.value?.details || []);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const profileDetails = [
    { label: "Number", value: "number", class: "text-xl font-bold" },
    {
        label: "Purchase Order",
        value: (row) => row.purchase_order?.number,
        class: "text-gray-500",
    },
    {
        label: "Date",
        value: (row) => moment(row.date).format("MMM D, YYYY"),
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Status",
        value: (row) => humanReadable(row.status),
        class: "text-gray-600",
    },
    {
        label: "Notes",
        value: (row) => row.notes,
        class: "text-gray-600",
    },
];

const toast = useToast();
const items = ref([]);
const supplierProducts = ref([]);
const isLoading = ref(false);
const isLoadingProducts = ref(false);
const hasLoadedProducts = ref(false);
const selectedProduct = ref(null);
const itemForm = ref({});
const purchaseOrderDetails = ref([]);
const showEditModal = ref(false);
const editingDetail = ref(null);
const showReceiveModal = ref(false);
const showReturnModal = ref(false);
const selectedDetail = ref(null);
const hasSerials = ref(false);
const serialType = ref("serial_numbers");
const serials = ref([]);
const receivedQty = ref(0);
const returnQty = ref(0);
const returnNotes = ref("");

const receiveForm = ref({
    received_qty: 0,
    has_serials: false,
    type: "serial_numbers",
    serials: [],
});

const returnForm = ref({
    return_qty: 0,
    notes: "",
});

const bulkManufacturedDate = ref("");
const bulkExpiryDate = ref("");

const showDeleteSerialModal = ref(false);
const selectedSerialId = ref(null);
const selectedDetailId = ref(null);
const deleteRemarks = ref("");
const showEditSerialModal = ref(false);
const editingSerial = ref(null);

const loadSupplierProducts = async () => {
    if (!modelData.value?.supplier_id) {
        return;
    }

    try {
        isLoadingProducts.value = true;
        const response = await axios.get(
            `/api/suppliers/${modelData.value.supplier_id}/products`
        );
        const productsData = response.data || [];

        if (Array.isArray(productsData) && productsData.length > 0) {
            supplierProducts.value = productsData;
            hasLoadedProducts.value = true;
        } else {
            toast.error("No products available for this supplier");
            supplierProducts.value = [];
        }
    } catch (error) {
        console.error("Error loading supplier products:", error);
        toast.error("Failed to load supplier products");
        supplierProducts.value = [];
    } finally {
        isLoadingProducts.value = false;
    }
};

// Add computed total
const calculateTotal = (item) => {
    return (item.price || 0) * (item.qty || 0);
};

// Watch for changes in price or quantity to update total
const generateSerialCodes = () => {
    receiveForm.value.serials = receiveForm.value.serials.map(
        (serial, index) => {
            const prefix =
                receiveForm.value.type === "serial_numbers" ? "SN" : "BN";
            const code = `${prefix}-${Date.now()}-${index + 1}`;
            return {
                ...serial,
                [receiveForm.value.type === "serial_numbers"
                    ? "serial_number"
                    : "batch_number"]: code,
            };
        }
    );
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingDetail.value = null;
};

const saveEdit = async () => {
    try {
        isLoading.value = true;
        const payload = {
            supplier_product_detail_id:
                editingDetail.value.supplier_product_detail_id,
            qty: editingDetail.value.qty,
            free_qty: editingDetail.value.free_qty,
            price: editingDetail.value.price,
            discount: editingDetail.value.discount,
            total: calculateTotal(editingDetail.value),
        };

        await axios.put(
            `/api/purchase-orders/${modelData.value.id}/details/${editingDetail.value.id}`,
            payload
        );

        toast.success("Item updated successfully");
        await loadPurchaseOrderDetails();
        closeEditModal();
    } catch (error) {
        console.error("Error updating item:", error);
        toast.error(error.response?.data?.message || "Failed to update item");
    } finally {
        isLoading.value = false;
    }
};

const loadPurchaseOrderDetails = async () => {
    try {
        const response = await axios.get(
            `/api/purchase-orders/${modelData.value.purchase_order_id}/details`
        );
        purchaseOrderDetails.value = response.data.data || [];
    } catch (error) {
        console.error("Error loading purchase order details:", error);
        toast.error("Failed to load purchase order details");
    }
};

const handlePrint = () => {
    // Open the print window
    const printWindow = window.open(
        `${window.location.origin}/${modelName}/${modelData.value.id}/print`,
        "_blank"
    );

    // Wait for the window to load and trigger print
    printWindow.onload = function () {
        printWindow.print();
    };
};

const addSerialRow = () => {
    serials.value.push({
        serial_number: null,
        batch_number: null,
        manufactured_at: null,
        expired_at: null,
    });
};

const removeSerialRow = (index) => {
    serials.value.splice(index, 1);
};

const startReceive = (detail) => {
    selectedDetail.value = detail;
    receiveForm.value = {
        received_qty: detail.expected_qty - detail.received_qty,
        has_serials: false,
        type: "serial_numbers",
        serials: [],
    };
    showReceiveModal.value = true;
};

const startReturn = (detail) => {
    selectedDetail.value = detail;
    returnForm.value = {
        return_qty: detail.received_qty,
        notes: "",
    };
    showReturnModal.value = true;
};

const applyBulkDates = () => {
    if (receiveForm.value.serials.length > 0) {
        receiveForm.value.serials = receiveForm.value.serials.map((serial) => ({
            ...serial,
            manufactured_at:
                bulkManufacturedDate.value || serial.manufactured_at,
            expired_at: bulkExpiryDate.value || serial.expired_at,
        }));
    }
};

const handleSerialKeyDown = (event) => {
    // Prevent form submission on Enter key when scanning barcodes
    if (event.key === "Enter") {
        event.preventDefault();
    }
};

const handleReceive = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(
            `/api/goods-receipt-details/${selectedDetail.value.id}/receive`,
            receiveForm.value
        );

        // Handle partial success/failures
        if (response.data.results) {
            if (response.data.results.errors.length > 0) {
                response.data.results.errors.forEach((error) => {
                    toast.error(`Row ${error.index + 1}: ${error.message}`);
                });
            }
            if (response.data.results.success.length > 0) {
                toast.success(
                    `Successfully processed ${response.data.results.success.length} products`
                );
            }
        }

        closeReceiveModal();
        toast.success(`Successfully received product quantity`);
        router.get(`/${modelName}/${modelData.value.id}`);
    } catch (error) {
        console.error("Error receiving products:", error);
        toast.error(
            error.response?.data?.message || "Failed to receive products"
        );
    } finally {
        isLoading.value = false;
    }
};

const handleReturn = async () => {
    try {
        isLoading.value = true;
        await axios.post(
            `/api/goods-receipt-details/${selectedDetail.value.id}/return`,
            returnForm.value
        );
        toast.success("Products returned successfully");
        closeReturnModal();
        router.get(`/${modelName}/${modelData.value.id}`);
    } catch (error) {
        console.error("Error returning products:", error);
        toast.error(
            error.response?.data?.message || "Failed to return products"
        );
    } finally {
        isLoading.value = false;
    }
};

const addSerial = () => {
    if (receiveForm.value.has_serials) {
        const remainingQty =
            selectedDetail.value.expected_qty -
            selectedDetail.value.received_qty;
        if (receiveForm.value.serials.length >= remainingQty) {
            toast.error(
                `Cannot add more rows. Maximum quantity is ${remainingQty}`
            );
            return;
        }
    }

    receiveForm.value.serials.push({
        serial_number: "",
        batch_number: "",
        manufactured_at: bulkManufacturedDate.value || "",
        expired_at: bulkExpiryDate.value || "",
    });
};

const removeSerial = (index) => {
    if (
        receiveForm.value.has_serials &&
        receiveForm.value.serials.length <= 1
    ) {
        toast.error("At least one serial/batch number is required");
        return;
    }
    receiveForm.value.serials.splice(index, 1);
};

const closeReceiveModal = () => {
    showReceiveModal.value = false;
    selectedDetail.value = null;
    receiveForm.value = {
        received_qty: 0,
        has_serials: false,
        type: "serial_numbers",
        serials: [],
    };
};

const closeReturnModal = () => {
    showReturnModal.value = false;
    selectedDetail.value = null;
    returnForm.value = {
        return_qty: 0,
        notes: "",
    };
};

const submitReceive = async () => {
    try {
        isLoading.value = true;

        if (
            receiveForm.value.has_serials &&
            receiveForm.value.serials.length !==
                parseInt(receiveForm.value.received_qty)
        ) {
            toast.error(
                `Please add exactly ${receiveForm.value.received_qty} ${
                    receiveForm.value.type === "serial_numbers"
                        ? "serial numbers"
                        : "batch numbers"
                }`
            );
            return;
        }

        await axios.post(
            `/api/goods-receipt-details/${selectedDetail.value.id}/receive`,
            receiveForm.value
        );
        toast.success("Items received successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error receiving items:", error);
        toast.error(error.response?.data?.message || "Failed to receive items");
    } finally {
        isLoading.value = false;
    }
};

const submitReturn = async () => {
    try {
        isLoading.value = true;
        await axios.post(
            `/api/goods-receipt-details/${selectedDetail.value.id}/return`,
            returnForm.value
        );
        toast.success("Items returned successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error returning items:", error);
        toast.error(error.response?.data?.message || "Failed to return items");
    } finally {
        isLoading.value = false;
    }
};

const loadDetails = async () => {
    try {
        const response = await axios.get(
            `/api/goods-receipt-details?goods_receipt_id=${modelData.value.id}`
        );
        details.value = response.data;
    } catch (error) {
        console.error("Error loading details:", error);
        toast.error("Failed to load details");
    }
};

// Watch for changes in modelData.details
watch(
    () => modelData.value?.details,
    (newDetails) => {
        if (newDetails) {
            details.value = newDetails;
        }
    },
    { immediate: true }
);

const startDeleteSerial = (serialId, detailId) => {
    selectedSerialId.value = serialId;
    selectedDetailId.value = detailId;
    deleteRemarks.value = "";
    showDeleteSerialModal.value = true;
};

const closeDeleteSerialModal = () => {
    showDeleteSerialModal.value = false;
    selectedSerialId.value = null;
    selectedDetailId.value = null;
    deleteRemarks.value = "";
};

const handleDeleteSerial = async () => {
    if (!selectedSerialId.value || !selectedDetailId.value) return;

    try {
        isLoading.value = true;

        // First create the remark
        await axios.post("/api/goods-receipt-detail-remarks", {
            goods_receipt_detail_id: selectedDetailId.value,
            status: "deleted",
            remarks: deleteRemarks.value,
            is_serial: 1,
        });

        // Then delete the serial
        await axios.delete(
            `/api/goods-receipt-serials/${selectedSerialId.value}`
        );

        toast.success("Serial/batch number deleted successfully");
        closeDeleteSerialModal();
        router.get(`/${modelName}/${modelData.value.id}`);
    } catch (error) {
        console.error("Error deleting serial/batch number:", error);
        toast.error(
            error.response?.data?.message ||
                "Failed to delete serial/batch number"
        );
    } finally {
        isLoading.value = false;
    }
};

// Replace the existing deleteSerial function with startDeleteSerial
const deleteSerial = startDeleteSerial;

// Add this watch after other watches
watch(
    () => receiveForm.value.has_serials,
    (newValue) => {
        if (newValue) {
            // When toggling on, set quantity to match current serial rows (minimum 1)
            receiveForm.value.received_qty = Math.max(
                receiveForm.value.serials.length,
                1
            );
            if (receiveForm.value.serials.length === 0) {
                addSerial();
            }
        }
    }
);

watch(
    () => receiveForm.value.serials.length,
    (newLength) => {
        if (receiveForm.value.has_serials) {
            // When has_serials is true, quantity always matches number of rows
            receiveForm.value.received_qty = newLength;
        }
    }
);

onMounted(async () => {
    await loadSupplierProducts();
    await loadPurchaseOrderDetails();
    await loadDetails();
});

// Add this computed property after other computed properties
const isFullyReceived = computed(() => {
    if (!modelData.value?.details || modelData.value.details.length === 0)
        return false;
    return modelData.value.details.every(
        (detail) => detail.expected_qty === detail.received_qty
    );
});

// Add this method after other methods
const handleTransfer = async () => {
    if (!confirm("Are you sure you want to transfer these items to warehouse?"))
        return;

    try {
        isLoading.value = true;
        await axios.post(`/api/${modelName}/${modelData.value.id}/transfer`);
        toast.success("Items transferred to warehouse successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error transferring items:", error);
        toast.error(
            error.response?.data?.message || "Failed to transfer items"
        );
    } finally {
        isLoading.value = false;
    }
};

const startEditSerial = (serial) => {
    editingSerial.value = {
        ...serial,
        manufactured_at: serial.manufactured_at
            ? moment(serial.manufactured_at).format("YYYY-MM-DD")
            : "",
        expired_at: serial.expired_at
            ? moment(serial.expired_at).format("YYYY-MM-DD")
            : "",
    };
    showEditSerialModal.value = true;
};

const closeEditSerialModal = () => {
    showEditSerialModal.value = false;
    editingSerial.value = null;
};

const handleEditSerial = async () => {
    try {
        isLoading.value = true;
        const response = await axios.put(
            `/api/goods-receipt-serials/${editingSerial.value.id}`,
            editingSerial.value
        );
        toast.success("Serial/batch number updated successfully");
        closeEditSerialModal();
        router.get(`/${modelName}/${modelData.value.id}`);
    } catch (error) {
        console.error("Error updating serial/batch number:", error);
        toast.error(
            error.response?.data?.message ||
                "Failed to update serial/batch number"
        );
    } finally {
        isLoading.value = false;
    }
};

const handlePrintSerials = () => {
    // Open the print window
    const printWindow = window.open(
        `${window.location.origin}/${modelName}/${modelData.value.id}/print/serials`,
        "_blank"
    );

    // Wait for the window to load and trigger print
    printWindow.onload = function () {
        printWindow.print();
    };
};
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Supplier Invoice
                </h2>
                <div class="flex gap-2">
                    <Link
                        href="/goods-receipts"
                        class="border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600"
                    >
                        Go Back
                    </Link>
                    <button
                        v-if="modelData.status == 'in-warehouse'"
                        @click="handlePrint"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <DetailedProfileCard
                    :modelData="modelData"
                    :columns="profileDetails"
                />

                <div class="border-t border-gray-200 py-6">
                    <div class="px-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Details
                            </h3>
                            <button
                                v-if="
                                    isFullyReceived &&
                                    modelData.status !== 'in-warehouse'
                                "
                                @click="handleTransfer"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center"
                                :disabled="isLoading"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Transfer Items to Warehouse
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Product
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Variation
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                        >
                                            Expected Qty
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                        >
                                            Received Qty
                                        </th>
                                        <th
                                            class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                            v-if="
                                                modelData.status !=
                                                'in-warehouse'
                                            "
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="detail in modelData.details"
                                        :key="detail.id"
                                    >
                                        <td class="px-2 py-2">
                                            <div
                                                class="font-medium text-gray-900 flex items-center"
                                            >
                                                {{
                                                    detail.purchase_order_detail
                                                        ?.supplier_product_detail
                                                        ?.product?.name || "N/A"
                                                }}
                                                <svg
                                                    v-if="
                                                        detail.expected_qty ===
                                                        detail.received_qty
                                                    "
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 ml-2 text-green-500"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                {{
                                                    detail.purchase_order_detail
                                                        ?.supplier_product_detail
                                                        ?.variation?.name ||
                                                    "N/A"
                                                }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            {{
                                                formatNumber(
                                                    detail.expected_qty,
                                                    { minimumFractionDigits: 0 }
                                                )
                                            }}
                                        </td>
                                        <td class="px-2 py-2">
                                            {{
                                                formatNumber(
                                                    detail.received_qty,
                                                    { minimumFractionDigits: 0 }
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-2 py-2 text-right text-sm font-medium"
                                            v-if="
                                                modelData.status !=
                                                'in-warehouse'
                                            "
                                        >
                                            <div class="flex space-x-2">
                                                <button
                                                    v-if="
                                                        detail.expected_qty !==
                                                        detail.received_qty
                                                    "
                                                    @click="
                                                        startReceive(detail)
                                                    "
                                                    class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50"
                                                    :disabled="isLoading"
                                                    title="Receive"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                                <button
                                                    v-if="
                                                        detail.received_qty >
                                                            0 &&
                                                        !detail.has_serials
                                                    "
                                                    @click="startReturn(detail)"
                                                    class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                    :disabled="isLoading"
                                                    title="Return"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !modelData.details ||
                                            modelData.details.length === 0
                                        "
                                    >
                                        <td
                                            colspan="5"
                                            class="px-2 py-4 text-center text-gray-500"
                                        >
                                            No items found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Serial/Batch Numbers Section -->
                <div class="border-t border-gray-200 py-6">
                    <div class="px-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Serial/Batch Numbers
                            </h3>
                            <button
                                @click="handlePrintSerials"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                :disabled="isLoading"
                            >
                                <i class="mdi mdi-printer mr-2"></i>
                                Print Serials
                            </button>
                        </div>
                        <div class="border rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Product
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Serial Number
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Batch Number
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Manufactured Date
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Expiry Date
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                                v-if="
                                                    modelData.status !=
                                                    'in-warehouse'
                                                "
                                            >
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <template
                                            v-for="detail in modelData.details"
                                            :key="detail.id"
                                        >
                                            <tr
                                                v-for="serial in detail.serials"
                                                :key="serial.id"
                                                class="hover:bg-gray-50"
                                            >
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        detail
                                                            .purchase_order_detail
                                                            ?.supplier_product_detail
                                                            ?.product?.name ||
                                                        "N/A"
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        serial.serial_number ||
                                                        "-"
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        serial.batch_number ||
                                                        "-"
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        serial.manufactured_at
                                                            ? moment(
                                                                  serial.manufactured_at
                                                              ).format(
                                                                  "MMM D, YYYY"
                                                              )
                                                            : "-"
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        serial.expired_at
                                                            ? moment(
                                                                  serial.expired_at
                                                              ).format(
                                                                  "MMM D, YYYY"
                                                              )
                                                            : "-"
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-right"
                                                    v-if="
                                                        modelData.status !=
                                                        'in-warehouse'
                                                    "
                                                >
                                                    <div
                                                        class="flex space-x-2 justify-end"
                                                    >
                                                        <button
                                                            @click="
                                                                startEditSerial(
                                                                    serial
                                                                )
                                                            "
                                                            class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50"
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            title="Edit"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4"
                                                                viewBox="0 0 20 20"
                                                                fill="currentColor"
                                                            >
                                                                <path
                                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                                                />
                                                            </svg>
                                                        </button>
                                                        <button
                                                            @click="
                                                                deleteSerial(
                                                                    serial.id,
                                                                    detail.id
                                                                )
                                                            "
                                                            class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            title="Delete"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4"
                                                                viewBox="0 0 20 20"
                                                                fill="currentColor"
                                                            >
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr
                                            v-if="
                                                !modelData.details?.some(
                                                    (detail) =>
                                                        detail.serials?.length >
                                                        0
                                                )
                                            "
                                        >
                                            <td
                                                colspan="6"
                                                class="px-3 py-4 text-center text-gray-500"
                                            >
                                                No serial/batch numbers found
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Add Edit Modal -->
    <div
        v-if="showEditModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Edit Item Details
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Quantity</label
                    >
                    <input
                        type="number"
                        v-model="editingDetail.qty"
                        min="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Free Quantity</label
                    >
                    <input
                        type="number"
                        v-model="editingDetail.free_qty"
                        min="0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Price</label
                    >
                    <input
                        type="number"
                        v-model="editingDetail.price"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Discount</label
                    >
                    <input
                        type="number"
                        v-model="editingDetail.discount"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeEditModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="saveEdit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700"
                    :disabled="isLoading"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Receive Modal -->
    <div
        v-if="showReceiveModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4"
    >
        <div
            class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto"
        >
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Receive Items
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Quantity to Receive</label
                        >
                        <input
                            type="number"
                            v-model="receiveForm.received_qty"
                            min="0"
                            :max="
                                selectedDetail
                                    ? selectedDetail.expected_qty -
                                      selectedDetail.received_qty
                                    : 0
                            "
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                            :disabled="receiveForm.has_serials"
                            :title="
                                receiveForm.has_serials
                                    ? 'Quantity is based on the number of serial/batch numbers'
                                    : ''
                            "
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Has Serial/Batch Numbers</label
                        >
                        <div class="mt-1">
                            <input
                                type="checkbox"
                                v-model="receiveForm.has_serials"
                                class="rounded border-gray-300 text-gray-600 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="receiveForm.has_serials" class="overflow-x-auto">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Type</label
                        >
                        <select
                            v-model="receiveForm.type"
                            class="rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                        >
                            <option value="serial_numbers">
                                Serial Numbers
                            </option>
                            <option value="batch_numbers">Batch Numbers</option>
                        </select>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Bulk Manufactured Date</label
                                    >
                                    <div class="mt-1">
                                        <input
                                            type="date"
                                            v-model="bulkManufacturedDate"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Bulk Expiry Date</label
                                    >
                                    <div class="mt-1">
                                        <input
                                            type="date"
                                            v-model="bulkExpiryDate"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button
                                    @click="applyBulkDates"
                                    class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                    type="button"
                                >
                                    Apply Bulk Dates
                                </button>
                            </div>
                            <div class="mt-2">
                                <button
                                    @click="generateSerialCodes"
                                    class="w-full px-4 py-2 bg-gray-100 text-gray-800 rounded hover:bg-gray-200"
                                    type="button"
                                >
                                    Generate Serial Code
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{
                                                receiveForm.type ===
                                                "serial_numbers"
                                                    ? "Serial Number"
                                                    : "Batch Number"
                                            }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Manufactured Date
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Expiry Date
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                        >
                                            Actions
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
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                v-model="
                                                    serial[
                                                        receiveForm.type ===
                                                        'serial_numbers'
                                                            ? 'serial_number'
                                                            : 'batch_number'
                                                    ]
                                                "
                                                class="block w-full border-0 p-0 focus:ring-0 sm:text-sm"
                                                :placeholder="
                                                    receiveForm.type ===
                                                    'serial_numbers'
                                                        ? 'Enter serial number'
                                                        : 'Enter batch number'
                                                "
                                                @keydown="handleSerialKeyDown"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="date"
                                                v-model="serial.manufactured_at"
                                                class="block w-full border-0 p-0 focus:ring-0 sm:text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="date"
                                                v-model="serial.expired_at"
                                                class="block w-full border-0 p-0 focus:ring-0 sm:text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button
                                                @click="removeSerial(index)"
                                                class="text-red-600 hover:text-red-900"
                                                type="button"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button
                        @click="addSerial"
                        class="mt-4 text-sm text-gray-600 hover:text-gray-900 flex items-center"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-1"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Add
                        {{
                            receiveForm.type === "serial_numbers"
                                ? "Serial Number"
                                : "Batch Number"
                        }}
                    </button>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeReceiveModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleReceive"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700"
                    :disabled="isLoading"
                >
                    Receive
                </button>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div
        v-if="showReturnModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Return Items</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Quantity to Return</label
                    >
                    <input
                        type="number"
                        v-model="returnForm.return_qty"
                        min="0"
                        :max="selectedDetail ? selectedDetail.received_qty : 0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Notes</label
                    >
                    <textarea
                        v-model="returnForm.notes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeReturnModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleReturn"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700"
                    :disabled="isLoading"
                >
                    Return
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Serial Modal -->
    <div
        v-if="showDeleteSerialModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Delete Serial/Batch Number
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Remarks (Optional)</label
                    >
                    <textarea
                        v-model="deleteRemarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                        placeholder="Enter any remarks about this deletion..."
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeDeleteSerialModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleDeleteSerial"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700"
                    :disabled="isLoading"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Serial Modal -->
    <div
        v-if="showEditSerialModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Edit Serial/Batch Number
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Manufactured Date</label
                    >
                    <input
                        type="date"
                        v-model="editingSerial.manufactured_at"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Expiry Date</label
                    >
                    <input
                        type="date"
                        v-model="editingSerial.expired_at"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeEditSerialModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleEditSerial"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700"
                    :disabled="isLoading"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</template>
