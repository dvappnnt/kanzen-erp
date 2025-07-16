<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, formatNumber } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import QRCode from "qrcode.vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { Link } from "@inertiajs/vue3";
import _ from "lodash";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import Modal from "@/Components/Modal.vue";

const modelName = "warehouses";
const page = usePage();

const getQrUrl = (id) => {
    return route("qr.warehouses", { warehouse: id });
};

const getGoodsReceiptUrl = (id) => {
    return route("goods-receipts.show", { goods_receipt: id });
};

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
        label: "Company",
        value: (row) => row.company.name,
        class: "text-gray-600 font-semibold",
    },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.token),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const contactDetails = ref([
    { label: "Located At", value: "address" },
    { label: "Mobile", value: "mobile" },
    { label: "Landline", value: "landline" },
    { label: "Description", value: "description" },
    { label: "Website", value: "website" },
]);

const companyDetails = ref([
    { label: "Located At", value: "address" },
    { label: "Mobile", value: "mobile" },
    { label: "Landline", value: "landline" },
    { label: "Description", value: "description" },
    { label: "Website", value: "website" },
]);

const modelData = computed(() => page.props.modelData || {});

const warehouseProducts = ref([]);
const isLoading = ref(false);
const toast = useToast();

const showProductModal = ref(false);
const showEditPriceModal = ref(false);
const selectedProduct = ref(null);
const editForm = ref({
    price: 0,
    critical_level_qty: 0,
});

const filters = ref({
    search: "",
    product: "",
    variation: "",
    min_qty: "",
    max_qty: "",
    min_price: "",
    max_price: "",
});

const pagination = ref({
    current_page: 1,
    total: 0,
    per_page: 10,
    last_page: 0,
});

const showStockAdjustmentModal = ref(false);
const adjustmentForm = ref({
    system_quantity: 0,
    actual_quantity: 0,
    adjustment: 0,
    reason: "",
    remarks: "",
});

const showStockAdjustmentHistoryModal = ref(false);
const stockAdjustments = ref([]);
const stockAdjustmentsPagination = ref({
    current_page: 1,
    total: 0,
    per_page: 10,
    last_page: 0,
});

const showStockTransferModal = ref(false);
const transferForm = ref({
    quantity: 0,
    remarks: "",
    destination_warehouse: null,
    serials: [],
    serials_valid: true,
    serials_error: "",
});

const showStockTransferHistoryModal = ref(false);
const stockTransferHistory = ref([]);
const stockTransferHistoryPagination = ref({
    current_page: 1,
    total: 0,
    per_page: 10,
    last_page: 0,
});

// Add new refs for barcode modal
const showBarcodeModal = ref(false);
const barcodeForm = ref({
    barcode: "",
    sku: "",
});

const serialInput = ref("");
const serialValidationLoading = ref(false);
const serialValidationError = ref("");

const warehouses = ref([]);

const loadWarehouseProducts = async (page = 1) => {
    try {
        isLoading.value = true;

        // Build query parameters
        const params = new URLSearchParams();
        params.append("page", page);

        // Add filters if they have values
        Object.entries(filters.value).forEach(([key, value]) => {
            if (value !== "" && value !== null) {
                params.append(key, value);
            }
        });

        const response = await axios.get(
            `/api/warehouses/${
                modelData.value.id
            }/products?${params.toString()}`
        );

        // Handle both paginated and non-paginated responses
        if (response.data.meta) {
            // Paginated response
            warehouseProducts.value = response.data.data;
            pagination.value = {
                current_page: response.data.meta.current_page,
                total: response.data.meta.total,
                per_page: response.data.meta.per_page,
                last_page: response.data.meta.last_page,
            };
        } else if (Array.isArray(response.data.data)) {
            // Non-paginated response
            warehouseProducts.value = response.data.data;
            pagination.value = {
                current_page: 1,
                total: response.data.data.length,
                per_page: response.data.data.length,
                last_page: 1,
            };
        } else {
            // Empty or invalid response
            warehouseProducts.value = [];
            pagination.value = {
                current_page: 1,
                total: 0,
                per_page: 10,
                last_page: 1,
            };
        }
    } catch (error) {
        console.error("Error loading warehouse products:", error);
        // Reset data on error
        warehouseProducts.value = [];
        pagination.value = {
            current_page: 1,
            total: 0,
            per_page: 10,
            last_page: 1,
        };
        toast.error("Failed to load warehouse products");
    } finally {
        isLoading.value = false;
    }
};

const debouncedSearch = _.debounce(() => {
    pagination.value.current_page = 1; // Reset to first page when filtering
    loadWarehouseProducts(1);
}, 300);

watch(
    filters,
    () => {
        debouncedSearch();
    },
    { deep: true }
);

const openProductModal = (product) => {
    selectedProduct.value = product;
    showProductModal.value = true;
};

const openEditPriceModal = (product) => {
    selectedProduct.value = product;
    editForm.value = {
        price: product.price,
        critical_level_qty: product.critical_level_qty || 0,
    };
    showEditPriceModal.value = true;
};

const closeProductModal = () => {
    showProductModal.value = false;
    selectedProduct.value = null;
};

const closeEditPriceModal = () => {
    showEditPriceModal.value = false;
    selectedProduct.value = null;
    editForm.value = {
        price: 0,
        critical_level_qty: 0,
    };
};

const savePrice = async () => {
    try {
        isLoading.value = true;
        await axios.put(
            `/api/warehouse-products/${selectedProduct.value.id}`,
            editForm.value
        );
        toast.success("Product details updated successfully");
        await loadWarehouseProducts();
        closeEditPriceModal();
    } catch (error) {
        console.error("Error updating product:", error);
        toast.error(
            error.response?.data?.message || "Failed to update product"
        );
    } finally {
        isLoading.value = false;
    }
};

const openStockAdjustmentModal = (product) => {
    selectedProduct.value = product;
    adjustmentForm.value = {
        system_quantity: parseInt(product.qty) || 0,
        actual_quantity: parseInt(product.qty) || 0,
        adjustment: 0,
        reason: "",
        remarks: "",
    };
    showStockAdjustmentModal.value = true;
};

const closeStockAdjustmentModal = () => {
    showStockAdjustmentModal.value = false;
    selectedProduct.value = null;
    adjustmentForm.value = {
        system_quantity: 0,
        actual_quantity: 0,
        adjustment: 0,
        reason: "",
        remarks: "",
    };
};

const calculateAdjustment = () => {
    adjustmentForm.value.adjustment =
        adjustmentForm.value.actual_quantity -
        adjustmentForm.value.system_quantity;
};

watch(() => adjustmentForm.value.actual_quantity, calculateAdjustment);

const saveStockAdjustment = async () => {
    try {
        isLoading.value = true;

        // Convert string values to integers
        const formData = {
            warehouse_id: modelData.value.id,
            warehouse_product_id: selectedProduct.value.id,
            system_quantity: parseInt(adjustmentForm.value.system_quantity),
            actual_quantity: parseInt(adjustmentForm.value.actual_quantity),
            adjustment: parseInt(adjustmentForm.value.adjustment),
            reason: adjustmentForm.value.reason,
            remarks: adjustmentForm.value.remarks,
        };

        await axios.post(`/api/warehouse-stock-adjustments`, formData);
        toast.success("Stock adjustment saved successfully");
        await loadWarehouseProducts();
        closeStockAdjustmentModal();
    } catch (error) {
        console.error("Error saving stock adjustment:", error);
        toast.error(
            error.response?.data?.message || "Failed to save stock adjustment"
        );
    } finally {
        isLoading.value = false;
    }
};

const loadStockAdjustments = async (page = 1) => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/warehouse-stock-adjustments`, {
            params: {
                page,
                warehouse_id: modelData.value.id,
                warehouse_product_id: selectedProduct.value.id,
            },
        });

        stockAdjustments.value = response.data.data;
        stockAdjustmentsPagination.value = {
            current_page: response.data.current_page,
            total: response.data.total,
            per_page: response.data.per_page,
            last_page: response.data.last_page,
        };
    } catch (error) {
        console.error("Error loading stock adjustments:", error);
        toast.error("Failed to load stock adjustment history");
    } finally {
        isLoading.value = false;
    }
};

const openStockAdjustmentHistoryModal = async (product) => {
    selectedProduct.value = product;
    showStockAdjustmentHistoryModal.value = true;
    await loadStockAdjustments();
};

const closeStockAdjustmentHistoryModal = () => {
    showStockAdjustmentHistoryModal.value = false;
    selectedProduct.value = null;
    stockAdjustments.value = [];
};

const openStockTransferModal = (product) => {
    selectedProduct.value = product;
    transferForm.value = {
        quantity: 0,
        remarks: "",
        destination_warehouse: null,
        serials: [],
        serials_valid: true,
        serials_error: "",
    };
    showStockTransferModal.value = true;
};

const closeStockTransferModal = () => {
    showStockTransferModal.value = false;
    selectedProduct.value = null;
    transferForm.value = {
        quantity: 0,
        remarks: "",
        destination_warehouse: null,
        serials: [],
        serials_valid: true,
        serials_error: "",
    };
};

const handleWarehouseSelect = (response) => {
    if (response?.data?.[0]) {
        transferForm.value.destination_warehouse = {
            id: response.data[0].id,
            name: response.data[0].name,
            company: response.data[0].company,
        };
    }
};

// Add watch for serials to update quantity
watch(
    () => transferForm.value.serials,
    (newSerials) => {
        if (selectedProduct.value?.has_serials) {
            transferForm.value.quantity = newSerials.length;
        }
    },
    { deep: true }
);

const validateSerial = async (serial) => {
    if (!serial || !selectedProduct.value) return;

    try {
        serialValidationLoading.value = true;
        serialValidationError.value = "";

        const response = await axios.get(
            "/api/serial-check/warehouse-products",
            {
                params: {
                    warehouse_id: modelData.value.id,
                    serial_number: serial,
                    product_id: selectedProduct.value.id,
                },
            }
        );

        if (response.data.data) {
            // Add serial to the list if not already present
            if (
                !transferForm.value.serials.some(
                    (s) => s.serial_number === serial
                )
            ) {
                transferForm.value.serials.push({
                    serial_number: serial,
                    batch_number: response.data.data.batch_number,
                    manufactured_at: response.data.data.manufactured_at,
                    expired_at: response.data.data.expired_at,
                });
            }
            serialInput.value = ""; // Clear input after successful addition
        } else {
            serialValidationError.value = "Invalid serial number";
        }
    } catch (error) {
        serialValidationError.value =
            error.response?.data?.message || "Failed to validate serial number";
    } finally {
        serialValidationLoading.value = false;
    }
};

const removeSerial = (idx) => {
    transferForm.value.serials.splice(idx, 1);
};

const saveStockTransfer = async () => {
    try {
        isLoading.value = true;

        // Validate serials if product requires them
        if (
            selectedProduct.value.has_serials &&
            transferForm.value.serials.length !== transferForm.value.quantity
        ) {
            toast.error("Please enter all required serial numbers");
            return;
        }

        const formData = {
            origin_warehouse_id: modelData.value.id,
            destination_warehouse_id:
                transferForm.value.destination_warehouse.id,
            transfer_date: new Date().toISOString().split("T")[0],
            details: [
                {
                    origin_warehouse_product_id: selectedProduct.value.id,
                    expected_qty: parseInt(transferForm.value.quantity),
                    serials: transferForm.value.serials,
                },
            ],
        };

        await axios.post(`/api/warehouse-stock-transfers`, formData);
        toast.success("Stock transfer created successfully");
        await loadWarehouseProducts();
        closeStockTransferModal();
    } catch (error) {
        console.error("Error saving stock transfer:", error);
        toast.error(
            error.response?.data?.message || "Failed to save stock transfer"
        );
    } finally {
        isLoading.value = false;
    }
};

const loadStockTransferHistory = async (page = 1) => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/warehouse-stock-transfers`, {
            params: {
                page,
                warehouse_id: modelData.value.id,
                warehouse_product_id: selectedProduct.value.id,
            },
        });

        stockTransferHistory.value = response.data.data;
        stockTransferHistoryPagination.value = {
            current_page: response.data.current_page,
            total: response.data.total,
            per_page: response.data.per_page,
            last_page: response.data.last_page,
        };
    } catch (error) {
        console.error("Error loading stock transfer history:", error);
        toast.error("Failed to load stock transfer history");
    } finally {
        isLoading.value = false;
    }
};

const openStockTransferHistoryModal = async (product) => {
    selectedProduct.value = product;
    showStockTransferHistoryModal.value = true;
    await loadStockTransferHistory();
};

const closeStockTransferHistoryModal = () => {
    showStockTransferHistoryModal.value = false;
    selectedProduct.value = null;
    stockTransferHistory.value = [];
};

// Add new methods for barcode functionality
const openBarcodeModal = (product) => {
    selectedProduct.value = product;
    barcodeForm.value = {
        barcode: product.supplier_product_detail?.variation?.barcode || "",
        sku: product.supplier_product_detail?.variation?.sku || "",
    };
    showBarcodeModal.value = true;
};

const closeBarcodeModal = () => {
    showBarcodeModal.value = false;
    selectedProduct.value = null;
    barcodeForm.value = {
        barcode: "",
        sku: "",
    };
};

const saveBarcodeSku = async () => {
    try {
        isLoading.value = true;
        await axios.put(
            `/api/warehouse-products/${selectedProduct.value.id}/update/barcode-sku`,
            barcodeForm.value
        );
        toast.success("Barcode and SKU updated successfully");
        await loadWarehouseProducts();
        closeBarcodeModal();
    } catch (error) {
        console.error("Error updating barcode and SKU:", error);
        toast.error(
            error.response?.data?.message || "Failed to update barcode and SKU"
        );
    } finally {
        isLoading.value = false;
    }
};

const loadWarehouses = async () => {
    try {
        const response = await axios.get("/api/warehouses");
        warehouses.value = response.data.data.filter(
            (w) => w.id !== modelData.value.id
        );
    } catch (error) {
        console.error("Error loading warehouses:", error);
    }
};

onMounted(() => {
    loadWarehouseProducts();
    loadWarehouses();
});
const printArea = ref(null);

const printStockAdjustment = () => {
    if (!printArea.value) return;

    const content = printArea.value.innerHTML;

    const printWindow = window.open("", "_blank", "width=900,height=600");
    printWindow.document.write(`
        <html>
            <head>
                <title>Stock Adjustment History</title>
                <style>
                    body { font-family: sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f9f9f9; }
                    h2 { margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <h2>Stock Adjustment History</h2>
                ${content}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
};
const printWarehouseProducts = () => {
    const container = document.querySelector(".overflow-x-auto");

    if (!container) return;

    // Clone the HTML so we can safely modify it without affecting the live DOM
    const clone = container.cloneNode(true);

    // Remove all elements with Actions header or class (e.g. by column index or known class)
    const actionHeaderIndex = Array.from(clone.querySelectorAll("thead th")).findIndex(th =>
        th.textContent.trim().toLowerCase() === "actions"
    );

    // Remove "Actions" <th>
    if (actionHeaderIndex > -1) {
        clone.querySelectorAll("thead tr").forEach(tr => {
            tr.children[actionHeaderIndex]?.remove();
        });

        // Remove each corresponding <td> in tbody
        clone.querySelectorAll("tbody tr").forEach(tr => {
            tr.children[actionHeaderIndex]?.remove();
        });
    }

    const printContents = clone.innerHTML;

    const newWindow = window.open("", "", "width=900,height=600");
    newWindow.document.write(`
        <html>
            <head>
                <title>Warehouse Stocks</title>
                <style>
                    body { font-family: sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th { background-color: #f9f9f9; }
                </style>
            </head>
            <body>
                <h2>Warehouse Stocks - ${modelData.value.name}</h2>
                ${printContents}
            </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
    newWindow.close();
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

        <div class="max-w-12xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />

                <!-- Warehouse Products Section -->
                <div class="py-6">
                    <div class="px-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Warehouse Products
                            </h3>

                            <button
                                @click="printWarehouseProducts"
                                class="flex items-center gap-1 text-sm text-gray-700 hover:text-gray-900"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v7a2 2 0 01-2 2h-2m-4 0h-4"
                                    />
                                </svg>
                                Print
                            </button>
                        </div>
                        <div class="border rounded-lg overflow-hidden">
                            <!-- Filters -->
                            <div
                                class="bg-white p-4 border-b border-gray-200 space-y-4"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                                >
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Product</label
                                        >
                                        <input
                                            type="text"
                                            v-model="filters.product"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Search product..."
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Variation</label
                                        >
                                        <input
                                            type="text"
                                            v-model="filters.variation"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Search variation..."
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Stock</label
                                        >
                                        <div class="mt-1 flex space-x-2">
                                            <input
                                                type="number"
                                                v-model="filters.min_qty"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Min"
                                            />
                                            <input
                                                type="number"
                                                v-model="filters.max_qty"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Max"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Price</label
                                        >
                                        <div class="mt-1 flex space-x-2">
                                            <input
                                                type="number"
                                                v-model="filters.min_price"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Min"
                                            />
                                            <input
                                                type="number"
                                                v-model="filters.max_price"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Max"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                                Variation
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Stock
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Selling Price
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Last Cost
                                            </th>
                                            <th
                                                class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                            >
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="product in warehouseProducts"
                                            :key="product.id"
                                            class="hover:bg-gray-50"
                                        >
                                            <td class="px-3 py-2">
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex-shrink-0 h-10 w-10"
                                                        v-if="
                                                            product
                                                                .supplier_product_detail
                                                                ?.product
                                                                ?.avatar
                                                        "
                                                    >
                                                        <img
                                                            class="h-10 w-10 rounded-full object-cover"
                                                            :src="`/storage/${product.supplier_product_detail.product.avatar}`"
                                                            :alt="
                                                                product
                                                                    .supplier_product_detail
                                                                    ?.product
                                                                    ?.name
                                                            "
                                                        />
                                                    </div>
                                                    <div class="ml-4">
                                                        <div
                                                            class="font-medium text-gray-900 flex items-center"
                                                        >
                                                            {{
                                                                product
                                                                    .supplier_product_detail
                                                                    ?.product
                                                                    ?.name ||
                                                                "-"
                                                            }}
                                                            <span
                                                                v-if="
                                                                    product.has_serials
                                                                "
                                                                class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                                                            >
                                                                Serial/Batch
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="text-sm text-gray-500"
                                                        >
                                                            SKU:
                                                            {{
                                                                product.sku ||
                                                                "-"
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-sm text-gray-500"
                                                        >
                                                            Barcode:
                                                            {{
                                                                product.barcode ||
                                                                "-"
                                                            }}
                                                            <div
                                                                class="text-sm text-gray-500"
                                                            >
                                                                <button
                                                                    v-if="
                                                                        product.stock_transfers_count >
                                                                        0
                                                                    "
                                                                    @click="
                                                                        openStockTransferHistoryModal(
                                                                            product
                                                                        )
                                                                    "
                                                                    class="text-blue-600 hover:text-blue-900 flex items-center"
                                                                >
                                                                    {{
                                                                        product.stock_transfers_count
                                                                    }}
                                                                    stock
                                                                    transfer(s)
                                                                    made
                                                                    <svg
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-5 w-5 ml-1"
                                                                        viewBox="0 0 20 20"
                                                                        fill="currentColor"
                                                                    >
                                                                        <path
                                                                            d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"
                                                                        />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900"
                                                >
                                                    {{
                                                        product
                                                            .supplier_product_detail
                                                            ?.variation?.name ||
                                                        "-"
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 flex items-center space-x-2"
                                                >
                                                    {{
                                                        formatNumber(
                                                            product.qty,
                                                            {
                                                                minimumFractionDigits: 0,
                                                            }
                                                        )
                                                    }}
                                                    <button
                                                        v-if="
                                                            product.stock_adjustments_count >
                                                            0
                                                        "
                                                        @click="
                                                            openStockAdjustmentHistoryModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-blue-600 hover:text-blue-900"
                                                        title="View Stock Adjustment History"
                                                    >
                                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                                                        </svg> -->
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="1.5"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"
                                                            />
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="1.5"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900"
                                                >
                                                    {{
                                                        formatNumber(
                                                            product.price,
                                                            {
                                                                style: "currency",
                                                                currency: "PHP",
                                                            }
                                                        )
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900"
                                                >
                                                    {{
                                                        formatNumber(
                                                            product.last_cost,
                                                            {
                                                                style: "currency",
                                                                currency: "PHP",
                                                            }
                                                        )
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="flex justify-center space-x-2"
                                                >
                                                    <button
                                                        @click="
                                                            openProductModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50"
                                                        title="View Details"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                d="M10 12a2 2 0 100-4 2 2 0 000 4z"
                                                            />
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="
                                                            openEditPriceModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                        title="Edit Price"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
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
                                                            openStockAdjustmentModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-orange-600 hover:text-orange-900 p-1 rounded-md hover:bg-orange-50"
                                                        title="Adjust Stock"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="
                                                            openStockTransferModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50"
                                                        title="Transfer Stock"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="
                                                            openBarcodeModal(
                                                                product
                                                            )
                                                        "
                                                        class="text-purple-600 hover:text-purple-900 p-1 rounded-md hover:bg-purple-50"
                                                        title="Edit Barcode/SKU"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                warehouseProducts.length === 0
                                            "
                                        >
                                            <td
                                                colspan="6"
                                                class="px-3 py-4 text-center text-gray-500"
                                            >
                                                No products found in this
                                                warehouse
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div
                                class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
                            >
                                <div
                                    class="flex-1 flex justify-between sm:hidden"
                                >
                                    <button
                                        @click="
                                            loadWarehouseProducts(
                                                pagination.current_page - 1
                                            )
                                        "
                                        :disabled="
                                            pagination.current_page === 1
                                        "
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        @click="
                                            loadWarehouseProducts(
                                                pagination.current_page + 1
                                            )
                                        "
                                        :disabled="
                                            pagination.current_page ===
                                            pagination.last_page
                                        "
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div
                                    class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{
                                                (pagination.current_page - 1) *
                                                    pagination.per_page +
                                                1
                                            }}</span>
                                            to
                                            <span class="font-medium">{{
                                                Math.min(
                                                    pagination.current_page *
                                                        pagination.per_page,
                                                    pagination.total
                                                )
                                            }}</span>
                                            of
                                            <span class="font-medium">{{
                                                pagination.total
                                            }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav
                                            class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination"
                                        >
                                            <button
                                                v-for="page in pagination.last_page"
                                                :key="page"
                                                @click="
                                                    loadWarehouseProducts(page)
                                                "
                                                :class="[
                                                    page ===
                                                    pagination.current_page
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                ]"
                                            >
                                                {{ page }}
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details Modal -->
        <div
            v-if="showProductModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4"
        >
            <div
                class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Product Details
                    </h3>
                    <button
                        @click="closeProductModal"
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

                <div class="space-y-6" v-if="selectedProduct">
                    <!-- Product Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Product Information
                        </h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-12 gap-6">
                                <!-- Left Column - Avatar -->
                                <div class="col-span-3">
                                    <div
                                        class="w-full aspect-square overflow-hidden rounded-lg bg-gray-100"
                                    >
                                        <img
                                            v-if="
                                                selectedProduct
                                                    .supplier_product_detail
                                                    ?.product?.avatar
                                            "
                                            :src="
                                                selectedProduct
                                                    .supplier_product_detail
                                                    .product.avatar
                                            "
                                            :alt="
                                                selectedProduct
                                                    .supplier_product_detail
                                                    ?.product?.name
                                            "
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center text-gray-400"
                                        >
                                            <svg
                                                class="w-12 h-12"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle Column - Information -->
                                <div class="col-span-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Name
                                            </p>
                                            <p class="mt-1">
                                                {{
                                                    selectedProduct
                                                        .supplier_product_detail
                                                        ?.product?.name
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                SKU
                                            </p>
                                            <p class="mt-1">
                                                {{ selectedProduct.sku }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Barcode
                                            </p>
                                            <p class="mt-1">
                                                {{ selectedProduct.barcode }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Unit of Measure
                                            </p>
                                            <p class="mt-1">
                                                {{
                                                    selectedProduct
                                                        .supplier_product_detail
                                                        ?.product
                                                        ?.unit_of_measure
                                                }}
                                            </p>
                                        </div>
                                        <div class="col-span-2">
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Variation
                                            </p>
                                            <p class="mt-1">
                                                {{
                                                    selectedProduct
                                                        .supplier_product_detail
                                                        ?.variation?.name || "-"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column - QR Code -->
                                <div
                                    class="col-span-3 flex flex-col items-center justify-center"
                                >
                                    <QRCode
                                        :value="getQrUrl(selectedProduct.id)"
                                        :size="128"
                                        level="H"
                                        class="border border-gray-200 p-1 rounded"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warehouse Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Warehouse Information
                        </h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        In Stock
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            formatNumber(selectedProduct.qty, {
                                                minimumFractionDigits: 0,
                                            })
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        Selling Price
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            formatNumber(
                                                selectedProduct.price,
                                                {
                                                    style: "currency",
                                                    currency: "PHP",
                                                }
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        Last Cost
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            formatNumber(
                                                selectedProduct.last_cost,
                                                {
                                                    style: "currency",
                                                    currency: "PHP",
                                                }
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        Average Cost
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            formatNumber(
                                                selectedProduct.average_cost,
                                                {
                                                    style: "currency",
                                                    currency: "PHP",
                                                }
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Serial/Batch Numbers if applicable -->
                    <div v-if="selectedProduct.has_serials">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Serial/Batch Numbers
                        </h4>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Serial Number
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Batch Number
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Manufactured Date
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Expiry Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="serial in selectedProduct.serials"
                                        :key="serial.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-3 py-2">
                                            {{ serial.serial_number || "-" }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ serial.batch_number || "-" }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                serial.manufactured_at
                                                    ? moment(
                                                          serial.manufactured_at
                                                      ).format("MMM D, YYYY")
                                                    : "-"
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                serial.expired_at
                                                    ? moment(
                                                          serial.expired_at
                                                      ).format("MMM D, YYYY")
                                                    : "-"
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transfer History -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Transfer History
                        </h4>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Date
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            From
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            To
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Created By
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            GR #
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Notes
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="transfer in selectedProduct.transfers"
                                        :key="transfer.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-3 py-2">
                                            {{
                                                moment(
                                                    transfer.created_at
                                                ).format("MMM D, YYYY")
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                transfer.origin_warehouse
                                                    ?.name || "-"
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                transfer.destination_warehouse
                                                    ?.name || "-"
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                transfer.created_by_user
                                                    ?.name || "-"
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <template
                                                v-if="transfer.goods_receipt_id"
                                            >
                                                <Link
                                                    :href="
                                                        getGoodsReceiptUrl(
                                                            transfer.goods_receipt_id
                                                        )
                                                    "
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    {{
                                                        transfer.goods_receipt
                                                            ?.number ||
                                                        transfer.goods_receipt_id
                                                    }}
                                                </Link>
                                            </template>
                                            <template v-else>-</template>
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ transfer.notes || "-" }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !selectedProduct.transfers?.length
                                        "
                                    >
                                        <td
                                            colspan="6"
                                            class="px-3 py-4 text-center text-gray-500"
                                        >
                                            No transfer history available
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Price Modal -->
        <div
            v-if="showEditPriceModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Edit Product Details
                    </h3>
                    <button
                        @click="closeEditPriceModal"
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

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Selling Price</label
                        >
                        <input
                            type="number"
                            v-model="editForm.price"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Critical Level Quantity</label
                        >
                        <input
                            type="number"
                            v-model="editForm.critical_level_qty"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeEditPriceModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="isLoading"
                    >
                        Cancel
                    </button>
                    <button
                        @click="savePrice"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                        :disabled="isLoading"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <div
            v-if="showStockAdjustmentModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Stock Adjustment
                    </h3>
                    <button
                        @click="closeStockAdjustmentModal"
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

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >System Quantity</label
                        >
                        <input
                            type="number"
                            v-model.number="adjustmentForm.system_quantity"
                            disabled
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Actual Quantity</label
                        >
                        <input
                            type="number"
                            v-model.number="adjustmentForm.actual_quantity"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Adjustment</label
                        >
                        <input
                            type="number"
                            v-model.number="adjustmentForm.adjustment"
                            disabled
                            :class="[
                                'mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                                adjustmentForm.adjustment > 0
                                    ? 'text-green-600'
                                    : adjustmentForm.adjustment < 0
                                    ? 'text-red-600'
                                    : '',
                            ]"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Reason</label
                        >
                        <select
                            v-model="adjustmentForm.reason"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select a reason</option>
                            <option value="damaged">Damaged</option>
                            <option value="lost">Lost</option>
                            <option value="count-correction">
                                Count Correction
                            </option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Remarks</label
                        >
                        <textarea
                            v-model="adjustmentForm.remarks"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter any additional remarks..."
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeStockAdjustmentModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="isLoading"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveStockAdjustment"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                        :disabled="isLoading || !adjustmentForm.reason"
                    >
                        Save Adjustment
                    </button>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment History Modal -->
        <div
            v-if="showStockAdjustmentHistoryModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4"
        >
            <div
                class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Stock Adjustment History
                    </h3>
                    <div class="flex gap-8 items-center">
                        <button
                            @click="printStockAdjustment"
                            class="text-gray-500 hover:text-gray-700"
                            title="Print"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v7a2 2 0 01-2 2h-2m-4 0h-4"
                                />
                            </svg>
                        </button>
                        <button
                            @click="closeStockAdjustmentHistoryModal"
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
                </div>

                <div class="overflow-x-auto" ref="printArea">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    System Qty
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Actual Qty
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Adjustment
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Reason
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Adjusted By
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Remarks
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="adjustment in stockAdjustments"
                                :key="adjustment.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        moment(adjustment.adjusted_at).format(
                                            "MMM D, YYYY HH:mm"
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        formatNumber(adjustment.system_quantity)
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        formatNumber(adjustment.actual_quantity)
                                    }}
                                </td>
                                <td
                                    class="px-3 py-2"
                                    :class="
                                        adjustment.adjustment > 0
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ formatNumber(adjustment.adjustment) }}
                                </td>
                                <td class="px-3 py-2 capitalize">
                                    {{ adjustment.reason }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        adjustment.adjusted_by_user?.name || "-"
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ adjustment.remarks || "-" }}
                                </td>
                            </tr>
                            <tr v-if="stockAdjustments.length === 0">
                                <td
                                    colspan="7"
                                    class="px-3 py-4 text-center text-gray-500"
                                >
                                    No stock adjustment history found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button
                            @click="
                                loadStockAdjustments(
                                    stockAdjustmentsPagination.current_page - 1
                                )
                            "
                            :disabled="
                                stockAdjustmentsPagination.current_page === 1
                            "
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </button>
                        <button
                            @click="
                                loadStockAdjustments(
                                    stockAdjustmentsPagination.current_page + 1
                                )
                            "
                            :disabled="
                                stockAdjustmentsPagination.current_page ===
                                stockAdjustmentsPagination.last_page
                            "
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </button>
                    </div>
                    <div
                        class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{
                                    (stockAdjustmentsPagination.current_page -
                                        1) *
                                        stockAdjustmentsPagination.per_page +
                                    1
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    Math.min(
                                        stockAdjustmentsPagination.current_page *
                                            stockAdjustmentsPagination.per_page,
                                        stockAdjustmentsPagination.total
                                    )
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    stockAdjustmentsPagination.total
                                }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav
                                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                aria-label="Pagination"
                            >
                                <button
                                    v-for="page in stockAdjustmentsPagination.last_page"
                                    :key="page"
                                    @click="loadStockAdjustments(page)"
                                    :class="[
                                        page ===
                                        stockAdjustmentsPagination.current_page
                                            ? 'relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                            : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                    ]"
                                >
                                    {{ page }}
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Transfer Modal -->
        <Modal :show="showStockTransferModal" @close="closeStockTransferModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Transfer Stock
                </h2>

                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">
                        Product Details
                    </h4>
                    <p class="text-sm text-gray-600">
                        {{
                            selectedProduct?.supplier_product_detail?.product
                                ?.name
                        }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Current Stock: {{ formatNumber(selectedProduct?.qty) }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Destination Warehouse</label
                    >
                    <select
                        v-model="transferForm.destination_warehouse"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    >
                        <option value="" disabled>Select a warehouse</option>
                        <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="warehouse"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Quantity</label
                    >
                    <input
                        type="number"
                        v-model="transferForm.quantity"
                        min="1"
                        :max="selectedProduct?.qty"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        :disabled="selectedProduct?.has_serials"
                    />
                    <p
                        v-if="selectedProduct?.has_serials"
                        class="mt-1 text-sm text-gray-500"
                    >
                        Quantity is automatically set based on the number of
                        serial numbers entered
                    </p>
                </div>

                <!-- Serial Numbers Section (if product has serials) -->
                <div v-if="selectedProduct?.has_serials" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Serial Numbers</label
                    >
                    <div class="mt-1">
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                v-model="serialInput"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                placeholder="Enter serial number"
                                @keyup.enter="validateSerial(serialInput)"
                                :disabled="serialValidationLoading"
                            />
                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                @click="validateSerial(serialInput)"
                                :disabled="
                                    serialValidationLoading || !serialInput
                                "
                            >
                                Add
                            </button>
                        </div>

                        <p
                            v-if="serialValidationError"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ serialValidationError }}
                        </p>

                        <!-- List of added serials -->
                        <div v-if="transferForm.serials.length" class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">
                                Added Serial Numbers
                            </h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(
                                        serial, idx
                                    ) in transferForm.serials"
                                    :key="idx"
                                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md"
                                >
                                    <div class="text-sm">
                                        <span class="font-medium">{{
                                            serial.serial_number
                                        }}</span>
                                        <span
                                            v-if="serial.batch_number"
                                            class="text-gray-500 ml-2"
                                        >
                                            Batch: {{ serial.batch_number }}
                                        </span>
                                        <span
                                            v-if="serial.manufactured_at"
                                            class="text-gray-500 ml-2"
                                        >
                                            Mfg: {{ serial.manufactured_at }}
                                        </span>
                                        <span
                                            v-if="serial.expired_at"
                                            class="text-gray-500 ml-2"
                                        >
                                            Exp: {{ serial.expired_at }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-900"
                                        @click="removeSerial(idx)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="
                                transferForm.quantity > 0 &&
                                transferForm.serials.length !==
                                    transferForm.quantity
                            "
                            class="mt-2 text-sm text-yellow-600"
                        >
                            Please enter {{ transferForm.quantity }} serial
                            numbers ({{ transferForm.serials.length }} added)
                        </p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Remarks</label
                    >
                    <textarea
                        v-model="transferForm.remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="closeStockTransferModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="saveStockTransfer"
                        :disabled="
                            isLoading ||
                            !transferForm.destination_warehouse ||
                            !transferForm.quantity ||
                            (selectedProduct?.has_serials &&
                                transferForm.serials.length !==
                                    transferForm.quantity)
                        "
                    >
                        {{ isLoading ? "Saving..." : "Save Transfer" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Stock Transfer History Modal -->
        <div
            v-if="showStockTransferHistoryModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4"
        >
            <div
                class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Stock Transfer History
                    </h3>
                    <button
                        @click="closeStockTransferHistoryModal"
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

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    From
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    To
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Quantity
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Created By
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Remarks
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="transfer in stockTransferHistory"
                                :key="transfer.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        moment(transfer.created_at).format(
                                            "MMM D, YYYY HH:mm"
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ transfer.origin_warehouse?.name || "-" }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        transfer.destination_warehouse?.name ||
                                        "-"
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ formatNumber(transfer.quantity) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ transfer.created_by_user?.name || "-" }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ transfer.remarks || "-" }}
                                </td>
                            </tr>
                            <tr v-if="stockTransferHistory.length === 0">
                                <td
                                    colspan="6"
                                    class="px-3 py-4 text-center text-gray-500"
                                >
                                    No stock transfer history found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button
                            @click="
                                loadStockTransferHistory(
                                    stockTransferHistoryPagination.current_page -
                                        1
                                )
                            "
                            :disabled="
                                stockTransferHistoryPagination.current_page ===
                                1
                            "
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </button>
                        <button
                            @click="
                                loadStockTransferHistory(
                                    stockTransferHistoryPagination.current_page +
                                        1
                                )
                            "
                            :disabled="
                                stockTransferHistoryPagination.current_page ===
                                stockTransferHistoryPagination.last_page
                            "
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </button>
                    </div>
                    <div
                        class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{
                                    (stockTransferHistoryPagination.current_page -
                                        1) *
                                        stockTransferHistoryPagination.per_page +
                                    1
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    Math.min(
                                        stockTransferHistoryPagination.current_page *
                                            stockTransferHistoryPagination.per_page,
                                        stockTransferHistoryPagination.total
                                    )
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    stockTransferHistoryPagination.total
                                }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav
                                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                aria-label="Pagination"
                            >
                                <button
                                    v-for="page in stockTransferHistoryPagination.last_page"
                                    :key="page"
                                    @click="loadStockTransferHistory(page)"
                                    :class="[
                                        page ===
                                        stockTransferHistoryPagination.current_page
                                            ? 'relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                            : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                    ]"
                                >
                                    {{ page }}
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barcode/SKU Edit Modal -->
        <div
            v-if="showBarcodeModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Edit Barcode and SKU
                    </h3>
                    <button
                        @click="closeBarcodeModal"
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

                <div class="space-y-4">
                    <div v-if="selectedProduct">
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">
                                Product Details
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{
                                    selectedProduct.supplier_product_detail
                                        ?.product?.name
                                }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Variation:
                                {{
                                    selectedProduct.supplier_product_detail
                                        ?.variation?.name || "-"
                                }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Barcode</label
                        >
                        <input
                            type="text"
                            v-model="barcodeForm.barcode"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter barcode..."
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >SKU</label
                        >
                        <input
                            type="text"
                            v-model="barcodeForm.sku"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter SKU..."
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeBarcodeModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="isLoading"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveBarcodeSku"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                        :disabled="isLoading"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
