<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import DetailedProfileCard from "@/Components/Sections/DetailedProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import {
    singularizeAndFormat,
    getStatusPillClass,
    humanReadable,
} from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import QRCode from "qrcode.vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { formatNumber, formatDate } from "@/utils/global";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import Modal from "@/Components/Modal.vue";
import axios from "@/axios";

const modelName = "warehouse-stock-transfers";
const page = usePage();

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const profileDetails = [
    { label: "Number", value: "number", class: "text-xl font-bold" },
    {
        label: "Company",
        value: (row) => row.origin_warehouse?.company?.name || "—",
        class: "text-gray-500",
    },
    {
        label: "Origin Warehouse",
        value: (row) => row.origin_warehouse?.name || "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Destination Warehouse",
        value: (row) => row.destination_warehouse?.name || "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Status",
        value: (row) => humanReadable(row.status),
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Transfer Date",
        value: (row) =>
            row.transfer_date ? formatDate("M d Y", row.transfer_date) : "—",
        class: "text-gray-600 font-semibold",
    },
];

const modelData = computed(() => page.props.modelData || {});
const originWarehouseId = computed(() => modelData.value.origin_warehouse_id);
const details = ref([]);
const showSerialModal = ref(false);
const serialModalRowIdx = ref(null);
const serialInput = ref("");
const serialsInputList = ref([]);
const serialValidationLoading = ref(false);
const serialValidationError = ref("");

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
const showRemarksModal = ref(false);
const showActionRemarksModal = ref(false);
const actionType = ref("");
const remarks = ref("");
const showConfirmModal = ref(false);
const confirmAction = ref("");
const confirmMessage = ref("");
const approvalLevels = ref([]);
const approvalRemarks = ref([]);
const selectedRemarks = ref([]);
const showReceiveModal = ref(false);
const showReturnModal = ref(false);
const selectedDetail = ref(null);
const receiveForm = ref({
    received_qty: 0,
    has_serials: false,
    type: "serial_numbers",
    serials: [],
});
const returnForm = ref({
    return_qty: 0,
    serials: [],
});
const bulkManufacturedDate = ref("");
const bulkExpiryDate = ref("");

const hasDetails = computed(() => {
    return purchaseOrderDetails.value.length > 0;
});

const calculateOrderTotal = computed(() => {
    return (
        purchaseOrderDetails.value?.reduce((sum, detail) => {
            const total = Number(detail.total) || 0;
            return sum + total;
        }, 0) || 0
    );
});

// Add computed properties for totals
const subtotal = computed(() => {
    return purchaseOrderDetails.value.reduce((sum, detail) => {
        return sum + (Number(detail.total) || 0);
    }, 0);
});

const taxAmount = computed(() => {
    return (subtotal.value * (modelData.value?.tax_rate || 0)) / 100;
});

const totalAmount = computed(() => {
    return (
        subtotal.value +
        taxAmount.value +
        (Number(modelData.value?.shipping_cost) || 0)
    );
});

const loadSupplierProducts = async () => {
    if (!modelData.value?.supplier_id) {
        toast.error("Supplier ID not found");
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

const handleProductSelect = (response) => {
    const product = response?.data?.[0];
    if (!product) return;
    // Prevent duplicate
    if (
        details.value.some(
            (row) => row.origin_warehouse_product_id === product.id
        )
    ) {
        toast.error("Product already added.");
        return;
    }
    details.value.push({
        origin_warehouse_product_id: product.id,
        product,
        transfer_qty: 1,
        serials: [],
        serials_valid: true,
        serials_error: "",
    });
};

const mapWarehouseProductData = (data) => {
    // Accepts the API response and returns the product object
    return data.data || data;
};

const fetchWarehouseProductAutocomplete = async (search) => {
    // Used by Autocomplete to fetch with warehouse_id param
    if (!originWarehouseId.value) return [];
    const res = await axios.get(
        `/api/autocomplete/warehouse-products?search=${encodeURIComponent(
            search
        )}&warehouse_id=${originWarehouseId.value}`
    );
    return res.data.data || [];
};

const handleVariationSelect = (item) => {
    if (!item.product_id || !item.variation_id) {
        item.price = 0;
        item.supplier_product_detail_id = null;
        return;
    }

    const product = supplierProducts.value.find(
        (p) => p.id === parseInt(item.product_id)
    );
    if (product) {
        const detail = product.supplier_product_details.find(
            (d) => d.product_variation_id === parseInt(item.variation_id)
        );
        if (detail) {
            item.price = detail.price;
            item.supplier_product_detail_id = detail.id;
        }
    }
};

const addNewRow = async () => {
    if (!hasLoadedProducts.value) {
        await loadSupplierProducts();
    }

    if (supplierProducts.value.length === 0) {
        toast.error("No products available for this supplier");
        return;
    }

    items.value.push({
        id: Date.now(),
        product_id: "",
        variation_id: "",
        supplier_product_detail_id: null,
        qty: 1,
        free_qty: 0,
        discount: 0,
        price: 0,
        total: 0,
        notes: "",
    });
};

const removeRow = (idx) => {
    details.value.splice(idx, 1);
};

const saveAllRows = async () => {
    if (items.value.length === 0) {
        return;
    }

    try {
        isLoading.value = true;

        // Save all rows in the table
        for (const item of items.value) {
            if (!item.supplier_product_detail_id) {
                toast.error("Please select both product and variation");
                continue;
            }

            const payload = {
                supplier_product_detail_id: item.supplier_product_detail_id,
                qty: item.qty,
                free_qty: item.free_qty || 0,
                discount: item.discount || 0,
                price: item.price,
                total: calculateTotal(item),
                notes: item.notes,
            };

            await axios.post(
                `/api/warehouse-products/${modelData.value.id}/details`,
                payload
            );
        }

        toast.success("Items added successfully");
        items.value = []; // Clear the items
        await loadPurchaseOrderDetails(); // Reload the details
    } catch (error) {
        console.error("Error saving items:", error);
        toast.error(error.response?.data?.message || "Failed to save items");
    } finally {
        isLoading.value = false;
    }
};

const handleKeyDown = async (event, item) => {
    if (event.key === "Enter") {
        event.preventDefault();
        await saveAllRows();
    }
};

const getProductVariations = (productId) => {
    if (!productId) return [];
    const product = supplierProducts.value.find(
        (p) => p.id === parseInt(productId)
    );
    if (!product || !product.supplier_product_details) return [];

    return product.supplier_product_details.map((detail) => ({
        id: detail.product_variation_id,
        name:
            product.variations.find((v) => v.id === detail.product_variation_id)
                ?.name || "Unknown Variation",
        price: detail.price,
    }));
};

// Add computed total
const calculateTotal = (item) => {
    return (item.price || 0) * (item.qty || 0);
};

// Watch for changes in price or quantity to update total
const updateTotal = (item) => {
    item.total = calculateTotal(item);
};

const startEdit = (detail) => {
    editingDetail.value = {
        ...detail,
        supplier_product_detail_id: detail.supplier_product_detail_id,
        qty: detail.qty,
        free_qty: detail.free_qty,
        price: detail.price,
        discount: detail.discount,
    };
    showEditModal.value = true;
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
            `/api/warehouse-products/${modelData.value.id}/details/${editingDetail.value.id}`,
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

const deleteDetail = async (detailId) => {
    if (!confirm("Are you sure you want to delete this item?")) return;

    try {
        isLoading.value = true;
        await axios.delete(
            `/api/warehouse-products/${modelData.value.id}/details/${detailId}`
        );
        toast.success("Item deleted successfully");
        await loadPurchaseOrderDetails();
    } catch (error) {
        console.error("Error deleting item:", error);
        toast.error("Failed to delete item");
    } finally {
        isLoading.value = false;
    }
};

const loadWarehouseStockTransferDetails = async () => {
    try {
        const [detailsResponse, orderResponse] = await Promise.all([
            axios.get(`/api/warehouse-products/${modelData.value.id}`),
        ]);
        warehouseStockTransferDetails.value = detailsResponse.data.data || [];
        Object.assign(modelData.value, orderResponse.data);
    } catch (error) {
        console.error("Error loading warehouse stock transfer details:", error);
        toast.error("Failed to load warehouse stock transfer details");
    }
};

const handlePending = async () => {
    try {
        isLoading.value = true;
        await axios.post(
            `/api/warehouse-products/${modelData.value.id}/pending`
        );
        toast.success("Purchase order marked as pending");
        window.location.reload();
    } catch (error) {
        console.error("Error marking purchase order as pending:", error);
        toast.error(
            error.response?.data?.message || "Failed to mark as pending"
        );
    } finally {
        isLoading.value = false;
    }
};

const handleOrder = async () => {
    try {
        isLoading.value = true;

        // Call the order endpoint which will handle GR creation
        const response = await axios.post(
            `/api/warehouse-products/${modelData.value.id}/order`
        );

        toast.success("Order processed successfully");

        // Redirect to the newly created goods receipt if available
        if (response.data?.data?.goods_receipt_id) {
            router.visit(
                `/goods-receipts/${response.data.data.goods_receipt_id}`
            );
        } else {
            window.location.reload();
        }
    } catch (error) {
        console.error("Error processing order:", error);
        toast.error(error.response?.data?.message || "Failed to process order");
    } finally {
        isLoading.value = false;
    }
};

const handlePrint = () => {
    // Open the print window
    const printWindow = window.open(
        `${window.location.origin}/warehouse-products/${modelData.value.id}/print`,
        "_blank"
    );

    // Wait for the window to load and trigger print
    printWindow.onload = function () {
        printWindow.print();
    };
};

const openRemarksViewModal = (remarks) => {
    console.log("openRemarksViewModal called with:", remarks); // Debug log
    selectedRemarks.value = remarks;
    showRemarksModal.value = true;
    console.log("Modal state after opening:", showRemarksModal.value); // Debug log
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

const handleAction = async () => {
    try {
        isLoading.value = true;
        let endpoint = "";
        let successMessage = "";

        switch (actionType.value) {
            case "approve":
                endpoint = "approve";
                successMessage = "Purchase order approved successfully";
                break;
            case "reject":
                endpoint = "reject";
                successMessage = "Purchase order rejected successfully";
                break;
            case "cancel":
                endpoint = "cancel";
                successMessage = "Purchase order cancelled successfully";
                break;
        }

        // First, create the approval remark if remarks exist
        if (remarks.value.trim()) {
            await axios.post("/api/approval-remarks", {
                model_type: "PurchaseOrder",
                model_id: modelData.value.id,
                purchase_order_id: modelData.value.id,
                status: actionType.value,
                remarks: remarks.value,
            });
        }

        // Then update the purchase order status
        await axios.post(
            `/api/warehouse-products/${modelData.value.id}/${endpoint}`
        );

        toast.success(successMessage);
        closeActionRemarksModal();
        window.location.reload();
    } catch (error) {
        console.error(`Error ${actionType.value}ing purchase order:`, error);
        toast.error(
            error.response?.data?.message ||
                `Failed to ${actionType.value} purchase order`
        );
    } finally {
        isLoading.value = false;
    }
};

// Update the button click handlers
const handleApprove = () => openActionRemarksModal("approve");
const handleReject = () => openActionRemarksModal("reject");
const handleCancel = () => openActionRemarksModal("cancel");

const loadApprovalLevels = async () => {
    try {
        const response = await axios.get(
            `/api/warehouse-products/${modelData.value.id}/approval-levels`
        );
        approvalLevels.value = response.data.data || [];
    } catch (error) {
        console.error("Error loading approval levels:", error);
        toast.error("Failed to load approval levels");
    }
};

const openConfirmModal = (action) => {
    confirmAction.value = action;
    switch (action) {
        case "submit":
            confirmMessage.value =
                "Are you sure you want to submit this purchase order for approval?";
            break;
        case "process":
            confirmMessage.value =
                "Are you sure you want to process this order? This will create a goods receipt and supplier invoice.";
            break;
    }
    showConfirmModal.value = true;
};

const handleConfirmAction = async () => {
    try {
        isLoading.value = true;
        switch (confirmAction.value) {
            case "submit":
                await handlePending();
                break;
            case "process":
                await handleOrder();
                break;
        }
        showConfirmModal.value = false;
    } catch (error) {
        console.error(`Error in ${confirmAction.value}:`, error);
        toast.error(
            error.response?.data?.message || `Failed to ${confirmAction.value}`
        );
    } finally {
        isLoading.value = false;
    }
};

// Update the existing button click handlers
const handleSubmitForApproval = () => openConfirmModal("submit");
const handleProcessOrder = () => openConfirmModal("process");

const loadApprovalRemarks = async () => {
    try {
        const response = await axios.get(
            `/api/warehouse-products/${modelData.value.id}/approval-remarks`
        );
        console.log("Approval remarks response:", response.data); // Debug log
        approvalRemarks.value = Array.isArray(response.data)
            ? response.data
            : response.data.data || [];
    } catch (error) {
        console.error("Error loading approval remarks:", error);
        toast.error("Failed to load approval remarks");
    }
};

const openReceiveModal = (detail) => {
    selectedDetail.value = detail;
    receiveForm.value = {
        received_qty: detail.expected_qty - detail.transferred_qty,
        has_serials: detail.origin_warehouse_product.has_serials,
        type: "serial_numbers",
        serials: [],
    };
    showReceiveModal.value = true;
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
const openReturnModal = (detail) => {
    selectedDetail.value = detail;
    returnForm.value = {
        return_qty: detail.transferred_qty,
        serials: [],
    };
    showReturnModal.value = true;
};
const closeReturnModal = () => {
    showReturnModal.value = false;
    selectedDetail.value = null;
    returnForm.value = { return_qty: 0, serials: [] };
};
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
const handleReceive = async () => {
    if (isLoading.value) return;
    isLoading.value = true;
    try {
        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/receive`,
            receiveForm.value
        );
        toast.success("Received successfully");
        window.location.reload();
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to receive");
    } finally {
        isLoading.value = false;
        closeReceiveModal();
    }
};
const handleReturn = async () => {
    if (isLoading.value) return;
    isLoading.value = true;
    try {
        await axios.post(
            `/api/warehouse-stock-transfer-details/${selectedDetail.value.id}/return`,
            returnForm.value
        );
        toast.success("Returned successfully");
        window.location.reload();
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to return");
    } finally {
        isLoading.value = false;
        closeReturnModal();
    }
};

// Add computed for transfer status
const transferStatus = computed(() => modelData.value.status);
const canShowForTransfer = computed(() =>
    modelData.value.status === "pending" && details.value.length > 0
);
const showAddProductModal = ref(false);

function openAddProductModal() {
    showAddProductModal.value = true;
}
function closeAddProductModal() {
    showAddProductModal.value = false;
}

const openSerialModal = (rowIdx) => {
    serialModalRowIdx.value = rowIdx;
    serialsInputList.value = [...(details.value[rowIdx].serials || [])];
    serialInput.value = "";
    serialValidationError.value = "";
    showSerialModal.value = true;
};
const closeSerialModal = () => {
    showSerialModal.value = false;
    serialModalRowIdx.value = null;
    serialsInputList.value = [];
    serialInput.value = "";
    serialValidationError.value = "";
};
const addSerialToList = async () => {
    const serial = serialInput.value.trim();
    if (!serial) return;
    serialValidationLoading.value = true;
    serialValidationError.value = "";
    try {
        const row = details.value[serialModalRowIdx.value];
        const product = row.product;
        // Use the correct API for serial check
        const res = await axios.get("/api/serial-check/warehouse-products", {
            params: {
                warehouse_id: modelData.value.origin_warehouse_id,
                serial_number: serial,
                product_id: product.id,
            },
        });
        const found = res.data.data;
        if (!found) {
            serialValidationError.value = "Serial/batch not found, already sold, or does not belong to this product.";
            return;
        }
        if (serialsInputList.value.some((s) => s.serial_number === serial)) {
            serialValidationError.value = "Serial already added.";
            return;
        }
        serialsInputList.value.push({
            serial_number: serial,
            batch_number: found.batch_number,
            manufactured_at: found.manufactured_at,
            expired_at: found.expired_at,
        });
        serialInput.value = "";
    } catch (e) {
        serialValidationError.value = "Error validating serial/batch.";
    } finally {
        serialValidationLoading.value = false;
    }
};
const saveSerialsToRow = () => {
    if (serialModalRowIdx.value !== null) {
        details.value[serialModalRowIdx.value].serials = [
            ...serialsInputList.value,
        ];
        details.value[serialModalRowIdx.value].serials_valid =
            serialsInputList.value.length ===
            details.value[serialModalRowIdx.value].transfer_qty;
        details.value[serialModalRowIdx.value].serials_error = details.value[
            serialModalRowIdx.value
        ].serials_valid
            ? ""
            : "Serial count must match transfer qty.";
    }
    closeSerialModal();
};

const updateTransferQty = (row, val) => {
    row.transfer_qty = Math.max(1, parseInt(val) || 1);
    // Only require serial count to match qty if product has serials
    if (row.product.has_serials) {
        row.serials_valid = row.serials.length === row.transfer_qty;
        row.serials_error = row.serials_valid
            ? ""
            : "Serial count must match transfer qty.";
    } else {
        row.serials_valid = true;
        row.serials_error = "";
    }
};

const canSubmit = computed(() => {
    return (
        details.value.length > 0 &&
        details.value.every((row) => {
            if (row.product.has_serials) {
                return (
                    row.serials_valid && row.serials.length === row.transfer_qty
                );
            }
            return row.transfer_qty > 0;
        })
    );
});

const submitTransferDetails = async () => {
    if (!canSubmit.value) {
        toast.error("Please complete all product details and serials.");
        return;
    }
    try {
        for (const row of details.value) {
            await axios.post("/api/warehouse-stock-transfer-details", {
                warehouse_stock_transfer_id: modelData.value.id,
                origin_warehouse_id: modelData.value.origin_warehouse_id,
                origin_warehouse_product_id: row.origin_warehouse_product_id,
                destination_warehouse_id: modelData.value.destination_warehouse_id,
                quantity: row.transfer_qty,
                serials: row.product.has_serials ? row.serials : [],
                remarks: null,
            });
        }
        toast.success("Transfer details saved.");
        window.location.reload();
    } catch (e) {
        toast.error(
            e.response?.data?.message || "Failed to save transfer details."
        );
    }
};

onMounted(() => {
    
    // Initialize details from modelData if present
    if (Array.isArray(modelData.value.details)) {
        details.value = modelData.value.details.map(d => ({
            ...d,
            product: d.product || d.origin_warehouse_product?.supplier_product_detail?.product || {},
            transfer_qty: d.expected_qty || 1,
            serials: d.serials || [],
            serials_valid: true,
            serials_error: "",
        }));
    }
});
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="[]" />
            </div>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6">
                    <DetailedProfileCard
                        :modelData="modelData"
                        :columns="profileDetails"
                        :columnsPerRow="3"
                    />
                </div>
                <!-- Action Buttons (approve/reject/cancel etc.) -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-if="['for-transfer'].includes(modelData.status)"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Submit for Approval
                    </button>

                    <button
                        v-if="['for-transfer'].includes(modelData.status)"
                        @click="handleApprove"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Approve
                    </button>

                    <button
                        v-if="['for-transfer'].includes(modelData.status)"
                        @click="handleReject"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Reject
                    </button>

                    <button
                        v-if="['pending'].includes(modelData.status)"
                        @click="handleCancel"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Cancel
                    </button>

                    <button
                        v-if="modelData.status === 'fully-approved'"
                        @click="handleProcessOrder"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Process Order
                    </button>

                    <button
                        v-if="
                            modelData.status === 'partially-approved' ||
                            modelData.status === 'received' ||
                            modelData.status === 'ordered'
                        "
                        @click="handlePrint"
                        :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                <div class="mb-4">
                    <Autocomplete
                        :search-url="'/api/autocomplete/warehouse-products'"
                        :placeholder="'Search warehouse products...'"
                        :model-name="'warehouse-products'"
                        :map-custom-buttons="mapWarehouseProductData"
                        @select="handleProductSelect"
                        :extra-params="{ warehouse_id: originWarehouseId }"
                    />
                </div>
                <div v-if="details.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Product
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    SKU
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Qty
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Serials
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, idx) in details"
                                :key="row.origin_warehouse_product_id"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        row.product?.supplier_product_detail
                                            ?.product?.name || row.product?.name
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ row.product?.sku }}
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        min="1"
                                        :value="row.transfer_qty"
                                        @input="
                                            (e) =>
                                                updateTransferQty(
                                                    row,
                                                    e.target.value
                                                )
                                        "
                                        class="w-20 border rounded px-2 py-1"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <template v-if="row.product.has_serials">
                                        <button
                                            class="px-2 py-1 bg-blue-100 rounded"
                                            @click="openSerialModal(idx)"
                                        >
                                            Enter Serial/Batch Number(s)
                                        </button>
                                        <div v-if="row.serials.length" class="text-xs text-gray-500 mt-1">
                                            <div v-for="s in row.serials" :key="s.serial_number">
                                                <span>{{ s.serial_number }}</span>
                                                <span v-if="s.batch_number">Batch: {{ s.batch_number }}</span>
                                                <span v-if="s.manufactured_at"> | Mfg: {{ s.manufactured_at }}</span>
                                                <span v-if="s.expired_at"> | Exp: {{ s.expired_at }}</span>
                                            </div>
                                        </div>
                                        <div v-if="row.serials_error" class="text-xs text-red-500 mt-1">
                                            {{ row.serials_error }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-400">N/A</span>
                                    </template>
                                </td>
                                <td class="px-3 py-2">
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="removeRow(idx)"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end">
                    <button
                        @click="submitTransferDetails"
                        :disabled="!canSubmit"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Save Transfer Details
                    </button>
                </div>
                <!-- After Save Transfer Details, show details table -->
                <div v-if="(modelData.value.details || []).length" class="mt-8">
                    <h3 class="text-lg font-semibold mb-2">Transfer Details</h3>
                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expected Qty</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Transferred Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in (modelData.value.details || [])" :key="detail.id">
                                <td class="px-3 py-2">{{ detail.origin_warehouse_product?.supplier_product_detail?.product?.name || detail.origin_warehouse_product?.name || 'N/A' }}</td>
                                <td class="px-3 py-2">{{ detail.origin_warehouse_product?.sku || 'N/A' }}</td>
                                <td class="px-3 py-2">{{ detail.expected_qty }}</td>
                                <td class="px-3 py-2">{{ detail.transferred_qty }}</td>
                            </tr>
                            <tr v-for="detail in (modelData.value.details || [])" :key="'serials-' + detail.id">
                                <td colspan="4" class="px-3 py-2 bg-gray-50">
                                    <div v-if="detail.serials && detail.serials.length">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead>
                                                <tr>
                                                    <th class="px-2 py-1 text-xs text-gray-500">Serial Number</th>
                                                    <th class="px-2 py-1 text-xs text-gray-500">Batch Number</th>
                                                    <th class="px-2 py-1 text-xs text-gray-500">Manufactured</th>
                                                    <th class="px-2 py-1 text-xs text-gray-500">Expired</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="serial in detail.serials" :key="serial.id">
                                                    <td class="px-2 py-1">{{ serial.serial_number }}</td>
                                                    <td class="px-2 py-1">{{ serial.batch_number || '-' }}</td>
                                                    <td class="px-2 py-1">{{ serial.manufactured_at || '-' }}</td>
                                                    <td class="px-2 py-1">{{ serial.expired_at || '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">No serials for this detail.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Serial Modal -->
        <Modal :show="showSerialModal" @close="closeSerialModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Enter Serial/Batch Number(s)
                </h2>
                <div class="mb-2 flex gap-2">
                    <input
                        v-model="serialInput"
                        @keyup.enter="addSerialToList"
                        :disabled="serialValidationLoading"
                        placeholder="Serial or batch number..."
                        class="w-full border rounded px-2 py-1"
                    />
                    <button
                </div>
            </div>
        </div>
        <!-- Serial Modal -->
        <Modal :show="showSerialModal" @close="closeSerialModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Enter Serial/Batch Number(s)
                </h2>
                <div class="mb-2 flex gap-2">
                    <input
                        v-model="serialInput"
                        @keyup.enter="addSerialToList"
                        :disabled="serialValidationLoading"
                        placeholder="Serial or batch number..."
                        class="w-full border rounded px-2 py-1"
                    />
                    <button
                        @click="addSerialToList"
                        :disabled="serialValidationLoading || !serialInput"
                        class="px-3 py-1 bg-blue-600 text-white rounded"
                    >
                        Add
                    </button>
                </div>
                <div v-if="serialValidationError" class="text-red-500 text-xs mb-2">
                    {{ serialValidationError }}
                </div>
                <div v-if="serialsInputList.length">
                    <table class="min-w-full divide-y divide-gray-200 mb-2">
                        <thead>
                            <tr>
                                <th class="px-2 py-1 text-xs text-gray-500">Serial Number</th>
                                <th class="px-2 py-1 text-xs text-gray-500">Batch Number</th>
                                <th class="px-2 py-1 text-xs text-gray-500">Manufactured</th>
                                <th class="px-2 py-1 text-xs text-gray-500">Expired</th>
                                <th class="px-2 py-1 text-xs text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, i) in serialsInputList" :key="i">
                                <td class="px-2 py-1">{{ s.serial_number }}</td>
                                <td class="px-2 py-1">{{ s.batch_number || '-' }}</td>
                                <td class="px-2 py-1">{{ s.manufactured_at || '-' }}</td>
                                <td class="px-2 py-1">{{ s.expired_at || '-' }}</td>
                                <td class="px-2 py-1">
                                    <button @click="serialsInputList.splice(i, 1)" class="text-red-500 text-xs">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        @click="closeSerialModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveSerialsToRow"
                        :disabled="details[serialModalRowIdx]?.product?.has_serials && serialsInputList.length !== details[serialModalRowIdx]?.transfer_qty"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Save
                    </button>
                </div>
            </div>
        </Modal>
        <!-- ...preserved action modals (approve/reject/cancel/remarks/confirm) go here... -->
    </AppLayout>
</template>
