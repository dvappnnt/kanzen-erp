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

const modelName = "purchase-orders";
const page = usePage();

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
    { label: "Number", value: "number", class: "text-xl font-bold" },
    {
        label: "Company",
        value: (row) => row.company.name,
        class: "text-gray-500",
    },
    {
        label: "Supplier",
        value: (row) => row.supplier.name,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Warehouse",
        value: (row) => row.warehouse.name,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Status",
        value: (row) => humanReadable(row.status),
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Order Date",
        value: (row) =>
            row.order_date ? formatDate("M d Y", row.order_date) : "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Expected Delivery Date",
        value: (row) =>
            row.expected_delivery_date
                ? formatDate("M d Y", row.expected_delivery_date)
                : "—",
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Payment Terms",
        value: (row) => row.payment_terms,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Shipping Terms",
        value: (row) => row.shipping_terms,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Notes",
        value: (row) => row.notes,
        class: "text-gray-600 font-semibold",
    },
    {
        label: "Remarks",
        value: (row) => {
            const remarks = approvalRemarks.value.filter(
                (r) => r.purchase_order_id === row.id
            );
            return remarks.length > 0 ? `${remarks.length} remark(s)` : "—";
        },
        class: "text-gray-600 font-semibold cursor-pointer hover:text-indigo-600",
        clickable: true,
        onClick: (row) => {
            console.log("Remarks clicked for row:", row); // Debug log
            const remarks = approvalRemarks.value.filter(
                (r) => r.purchase_order_id === row.id
            );
            console.log("Opening modal with remarks:", remarks); // Debug log
            if (remarks.length > 0) {
                openRemarksViewModal(remarks);
            }
        },
    },
];

const modelData = computed(() => page.props.modelData || {});

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

const handleProductSelect = async (item) => {
    if (!item.product_id) {
        item.variation_id = "";
        item.price = 0;
        item.supplier_product_detail_id = null;
        return;
    }

    const product = supplierProducts.value.find(
        (p) => p.id === parseInt(item.product_id)
    );
    if (product) {
        // If product has only one variation, select it automatically
        if (
            product.supplier_product_details &&
            product.supplier_product_details.length === 1
        ) {
            const detail = product.supplier_product_details[0];
            item.variation_id = detail.product_variation_id;
            item.price = detail.price;
            item.supplier_product_detail_id = detail.id;
        }
    }
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

const removeRow = (index) => {
    items.value.splice(index, 1);
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
                `/api/purchase-orders/${modelData.value.id}/details`,
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

const deleteDetail = async (detailId) => {
    if (!confirm("Are you sure you want to delete this item?")) return;

    try {
        isLoading.value = true;
        await axios.delete(
            `/api/purchase-orders/${modelData.value.id}/details/${detailId}`
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

const loadPurchaseOrderDetails = async () => {
    try {
        const [detailsResponse, orderResponse] = await Promise.all([
            axios.get(`/api/purchase-orders/${modelData.value.id}/details`),
            axios.get(`/api/purchase-orders/${modelData.value.id}`),
        ]);
        purchaseOrderDetails.value = detailsResponse.data.data || [];
        Object.assign(modelData.value, orderResponse.data);
    } catch (error) {
        console.error("Error loading purchase order details:", error);
        toast.error("Failed to load purchase order details");
    }
};

const handlePending = async () => {
    try {
        isLoading.value = true;
        await axios.post(`/api/purchase-orders/${modelData.value.id}/pending`);
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
            `/api/purchase-orders/${modelData.value.id}/order`
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
        `${window.location.origin}/purchase-orders/${modelData.value.id}/print`,
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
            `/api/purchase-orders/${modelData.value.id}/${endpoint}`
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
            `/api/purchase-orders/${modelData.value.id}/approval-levels`
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
            `/api/purchase-orders/${modelData.value.id}/approval-remarks`
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

onMounted(async () => {
    await loadSupplierProducts();
    await loadPurchaseOrderDetails();
    await loadApprovalLevels();
    await loadApprovalRemarks();
});
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

        <div class="max-w-12xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />

                <!-- Action Buttons -->
                <div class="px-6 py-4 flex justify-end space-x-3">
                    <button
                        v-if="modelData.status === 'draft' && hasDetails"
                        @click="handleSubmitForApproval"
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
                        v-if="modelData.status === 'pending'"
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
                        v-if="modelData.status === 'pending'"
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
                        v-if="['draft', 'pending'].includes(modelData.status)"
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

                <DetailedProfileCard
                    :modelData="modelData"
                    :columns="profileDetails"
                    :columnsPerRow="3"
                />

                <div class="border-t border-gray-200 py-6">
                    <div class="px-6">
                        <span v-if="modelData.status === 'draft'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Purchase Order Items
                            </h3>

                            <!-- Items Table -->
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead>
                                        <tr>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5"
                                            >
                                                Product
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5"
                                            >
                                                Variation
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"
                                            >
                                                Qty
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"
                                            >
                                                Free
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                            >
                                                Price
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                            >
                                                Total
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                            >
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="(item, index) in items"
                                            :key="item.id"
                                        >
                                            <td class="px-2 py-2">
                                                <select
                                                    v-model="item.product_id"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    @change="
                                                        handleProductSelect(
                                                            item
                                                        )
                                                    "
                                                    @keydown="
                                                        handleKeyDown(
                                                            $event,
                                                            item
                                                        )
                                                    "
                                                >
                                                    <option value="">
                                                        Select Product
                                                    </option>
                                                    <option
                                                        v-for="product in supplierProducts"
                                                        :key="product.id"
                                                        :value="product.id"
                                                    >
                                                        {{ product.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <select
                                                    v-model="item.variation_id"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    @change="
                                                        handleVariationSelect(
                                                            item
                                                        )
                                                    "
                                                    @keydown="
                                                        handleKeyDown(
                                                            $event,
                                                            item
                                                        )
                                                    "
                                                    :disabled="!item.product_id"
                                                >
                                                    <option value="">
                                                        Select Variation
                                                    </option>
                                                    <option
                                                        v-for="variation in getProductVariations(
                                                            item.product_id
                                                        )"
                                                        :key="variation.id"
                                                        :value="variation.id"
                                                    >
                                                        {{ variation.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    type="number"
                                                    v-model="item.qty"
                                                    min="1"
                                                    @input="updateTotal(item)"
                                                    @keydown="
                                                        handleKeyDown(
                                                            $event,
                                                            item
                                                        )
                                                    "
                                                    class="block w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    type="number"
                                                    v-model="item.free_qty"
                                                    min="0"
                                                    @keydown="
                                                        handleKeyDown(
                                                            $event,
                                                            item
                                                        )
                                                    "
                                                    class="block w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    type="number"
                                                    v-model="item.price"
                                                    step="0.01"
                                                    @input="updateTotal(item)"
                                                    @keydown="
                                                        handleKeyDown(
                                                            $event,
                                                            item
                                                        )
                                                    "
                                                    class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                />
                                            </td>
                                            <td class="px-2 py-2 text-gray-500">
                                                {{
                                                    calculateTotal(
                                                        item
                                                    ).toFixed(2)
                                                }}
                                            </td>
                                            <td
                                                class="px-2 py-2 text-right text-sm font-medium"
                                            >
                                                <div class="flex space-x-2">
                                                    <button
                                                        @click="startEdit(item)"
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                        :disabled="isLoading"
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
                                                            removeRow(index)
                                                        "
                                                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                        :disabled="isLoading"
                                                        title="Remove"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Add Row Button -->
                                        <tr>
                                            <td colspan="7" class="px-2 py-2">
                                                <button
                                                    @click="addNewRow"
                                                    class="text-sm text-indigo-600 hover:text-indigo-900 flex items-center"
                                                    :disabled="isLoading"
                                                >
                                                    <span class="mr-1">+</span>
                                                    Add Row
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </span>

                        <!-- Existing Purchase Order Details -->
                        <div
                            :class="
                                modelData.status === 'draft' ? 'mt-8' : 'mt-0'
                            "
                        >
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Details
                            </h3>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
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
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"
                                            >
                                                Qty
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"
                                            >
                                                Free
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                            >
                                                Price
                                            </th>
                                            <th
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                            >
                                                Total
                                            </th>
                                            <th
                                                v-if="
                                                    modelData.status ===
                                                        'draft' ||
                                                    modelData.status ===
                                                        'rejected'
                                                "
                                                class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                            >
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="detail in purchaseOrderDetails"
                                            :key="detail.id"
                                        >
                                            <td class="px-2 py-2">
                                                <div
                                                    class="font-medium text-gray-900"
                                                >
                                                    {{
                                                        detail
                                                            .supplier_product_detail
                                                            ?.product?.name ||
                                                        "N/A"
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <div
                                                    class="text-sm text-gray-600"
                                                >
                                                    {{
                                                        detail
                                                            .supplier_product_detail
                                                            ?.variation?.name ||
                                                        "N/A"
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                {{ detail.qty }}
                                            </td>
                                            <td class="px-2 py-2">
                                                {{ detail.free_qty }}
                                            </td>
                                            <td class="px-2 py-2">
                                                {{
                                                    formatNumber(detail.price, {
                                                        style: "currency",
                                                        currency: "PHP",
                                                    })
                                                }}
                                            </td>
                                            <td class="px-2 py-2">
                                                {{
                                                    formatNumber(detail.total, {
                                                        style: "currency",
                                                        currency: "PHP",
                                                    })
                                                }}
                                            </td>
                                            <td
                                                v-if="
                                                    modelData.status ===
                                                        'draft' ||
                                                    modelData.status ===
                                                        'rejected'
                                                "
                                                class="px-2 py-2 text-right text-sm font-medium"
                                            >
                                                <div class="flex space-x-2">
                                                    <button
                                                        @click="
                                                            startEdit(detail)
                                                        "
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                        :disabled="isLoading"
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
                                                            deleteDetail(
                                                                detail.id
                                                            )
                                                        "
                                                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                        :disabled="isLoading"
                                                        title="Delete"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                purchaseOrderDetails.length ===
                                                0
                                            "
                                        >
                                            <td
                                                colspan="7"
                                                class="px-2 py-4 text-center text-gray-500"
                                            >
                                                No items found
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Total Section -->
                                <div class="mt-4 flex justify-end px-2">
                                    <div class="w-64 space-y-2">
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >Subtotal:</span
                                            >
                                            <span class="font-medium">
                                                {{
                                                    formatNumber(subtotal, {
                                                        style: "currency",
                                                        currency: "PHP",
                                                    })
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >Tax Rate:</span
                                            >
                                            <span class="font-medium"
                                                >{{ modelData.tax_rate }}%</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >Tax Amount:</span
                                            >
                                            <span class="font-medium">
                                                {{
                                                    formatNumber(taxAmount, {
                                                        style: "currency",
                                                        currency: "PHP",
                                                    })
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >Shipping Cost:</span
                                            >
                                            <span class="font-medium">
                                                {{
                                                    formatNumber(
                                                        modelData.shipping_cost ||
                                                            0,
                                                        {
                                                            style: "currency",
                                                            currency: "PHP",
                                                        }
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="pt-2 border-t border-gray-200"
                                        >
                                            <div class="flex justify-between">
                                                <span
                                                    class="text-gray-800 font-semibold"
                                                    >Total Amount:</span
                                                >
                                                <span
                                                    class="text-xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        formatNumber(
                                                            totalAmount,
                                                            {
                                                                style: "currency",
                                                                currency: "PHP",
                                                            }
                                                        )
                                                    }}
                                                </span>
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    :disabled="isLoading"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Remarks View Modal -->
    <div
        v-if="showRemarksModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Approval Remarks
                </h3>
                <button
                    @click="showRemarksModal = false"
                    class="text-gray-400 hover:text-gray-500"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 max-h-96 overflow-y-auto">
                <div
                    v-for="remark in selectedRemarks"
                    :key="remark.id"
                    class="bg-gray-50 p-4 rounded-lg"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center space-x-3">
                            <img
                                v-if="
                                    remark.user && remark.user.profile_photo_url
                                "
                                :src="remark.user.profile_photo_url"
                                :alt="remark.user.name"
                                class="w-7 h-7 rounded-full object-cover border border-gray-200"
                            />
                            <span
                                v-if="remark.user"
                                class="text-sm font-semibold text-gray-700"
                            >
                                {{ remark.user.name }}
                            </span>
                            <span class="ml-2 text-sm text-gray-500">
                                {{ formatDate("M d Y", remark.created_at) }}
                            </span>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-4"
                            :class="{
                                'bg-green-100 text-green-800':
                                    remark.status === 'approve',
                                'bg-red-100 text-red-800':
                                    remark.status === 'reject',
                                'bg-yellow-100 text-yellow-800':
                                    remark.status === 'cancel',
                            }"
                        >
                            {{
                                remark.status.charAt(0).toUpperCase() +
                                remark.status.slice(1)
                            }}
                        </span>
                    </div>
                    <p class="pl-2 pt-2 text-gray-900 font-semibold text-base whitespace-pre-wrap">
                        {{ remark.remarks }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    @click="showRemarksModal = false"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Action Remarks Modal -->
    <div
        v-if="showActionRemarksModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{
                    actionType.charAt(0).toUpperCase() + actionType.slice(1)
                }}
                Purchase Order
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Remarks (Optional)</label
                    >
                    <textarea
                        v-model="remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Enter your remarks here..."
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="closeActionRemarksModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleAction"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white"
                    :class="{
                        'bg-green-600 hover:bg-green-700':
                            actionType === 'approve',
                        'bg-red-600 hover:bg-red-700':
                            actionType === 'reject' || actionType === 'cancel',
                    }"
                    :disabled="isLoading"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Add the confirmation modal -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Confirm Action
            </h3>

            <div class="space-y-4">
                <p class="text-gray-600">{{ confirmMessage }}</p>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    @click="showConfirmModal = false"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    @click="handleConfirmAction"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white"
                    :class="{
                        'bg-blue-600 hover:bg-blue-700':
                            confirmAction === 'submit',
                        'bg-indigo-600 hover:bg-indigo-700':
                            confirmAction === 'process',
                    }"
                    :disabled="isLoading"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>
</template>
