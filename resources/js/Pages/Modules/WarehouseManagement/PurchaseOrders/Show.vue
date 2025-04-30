<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import DetailedProfileCard from "@/Components/Sections/DetailedProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, getStatusPillClass, humanReadable } from "@/utils/global";
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
        const response = await axios.get(
            `/api/purchase-orders/${modelData.value.id}/details`
        );
        purchaseOrderDetails.value = response.data.data || [];
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

const handleCancel = async () => {
    if (!confirm("Are you sure you want to cancel this purchase order?"))
        return;

    try {
        isLoading.value = true;
        await axios.post(`/api/purchase-orders/${modelData.value.id}/cancel`);
        toast.success("Purchase order cancelled successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error cancelling purchase order:", error);
        toast.error(
            error.response?.data?.message || "Failed to cancel purchase order"
        );
    } finally {
        isLoading.value = false;
    }
};

const handleOrder = async () => {
    try {
        isLoading.value = true;
        
        // Call the order endpoint which will handle GR creation
        const response = await axios.post(`/api/purchase-orders/${modelData.value.id}/order`);
        
        toast.success("Order processed successfully");

        // Redirect to the newly created goods receipt if available
        if (response.data?.data?.goods_receipt_id) {
            router.visit(`/goods-receipts/${response.data.data.goods_receipt_id}`);
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

const handleReceive = async () => {
    try {
        isLoading.value = true;
        await axios.post(`/api/purchase-orders/${modelData.value.id}/receive`);
        toast.success("Purchase order marked as received");
        window.location.reload();
    } catch (error) {
        console.error("Error marking purchase order as received:", error);
        toast.error(
            error.response?.data?.message || "Failed to mark as received"
        );
    } finally {
        isLoading.value = false;
    }
};

const handlePrint = () => {
    // Open the print window
    const printWindow = window.open(`${window.location.origin}/purchase-orders/${modelData.value.id}/print`, '_blank');
    
    // Wait for the window to load and trigger print
    printWindow.onload = function() {
        printWindow.print();
    };
};

const handleApprove = async () => {
    try {
        isLoading.value = true;
        await axios.post(`/api/purchase-orders/${modelData.value.id}/approve`);
        toast.success("Purchase order approved successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error approving purchase order:", error);
        toast.error(error.response?.data?.message || "Failed to approve purchase order");
    } finally {
        isLoading.value = false;
    }
};

const handleReject = async () => {
    if (!confirm("Are you sure you want to reject this purchase order?")) return;
    
    try {
        isLoading.value = true;
        await axios.post(`/api/purchase-orders/${modelData.value.id}/reject`);
        toast.success("Purchase order rejected successfully");
        window.location.reload();
    } catch (error) {
        console.error("Error rejecting purchase order:", error);
        toast.error(error.response?.data?.message || "Failed to reject purchase order");
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    await loadSupplierProducts();
    await loadPurchaseOrderDetails();
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
                        v-if="modelData.status === 'draft'"
                        @click="handlePending"
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
                        v-if="modelData.status === 'approved'"
                        @click="handleOrder"
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
                        v-if="modelData.status === 'ordered'"
                        @click="handleReceive"
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
                                d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"
                            />
                            <path
                                d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"
                            />
                        </svg>
                        Receive
                    </button>

                    <button
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
                                            <th v-if="modelData.status === 'draft' || modelData.status === 'rejected'"
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
                                            <td v-if="modelData.status === 'draft' || modelData.status === 'rejected'"
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
</template>
