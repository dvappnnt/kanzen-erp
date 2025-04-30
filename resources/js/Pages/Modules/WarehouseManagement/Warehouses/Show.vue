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

const modelName = "warehouses";
const page = usePage();

const getQrUrl = (id) => {
    return route("qr.warehouses", { warehouse: id });
};

const getGoodsReceiptUrl = (id) => {
    return route('goods-receipts.show', { goods_receipt: id });
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
    critical_level_qty: 0
});

const filters = ref({
    search: '',
    product: '',
    variation: '',
    min_qty: '',
    max_qty: '',
    min_price: '',
    max_price: '',
});

const pagination = ref({
    current_page: 1,
    total: 0,
    per_page: 10,
    last_page: 0
});

const loadWarehouseProducts = async (page = 1) => {
    try {
        isLoading.value = true;
        
        // Build query parameters
        const params = new URLSearchParams();
        params.append('page', page);
        
        // Add filters if they have values
        Object.entries(filters.value).forEach(([key, value]) => {
            if (value !== '' && value !== null) {
                params.append(key, value);
            }
        });

        const response = await axios.get(
            `/api/warehouses/${modelData.value.id}/products?${params.toString()}`
        );

        // Handle both paginated and non-paginated responses
        if (response.data.meta) {
            // Paginated response
            warehouseProducts.value = response.data.data;
            pagination.value = {
                current_page: response.data.meta.current_page,
                total: response.data.meta.total,
                per_page: response.data.meta.per_page,
                last_page: response.data.meta.last_page
            };
        } else if (Array.isArray(response.data.data)) {
            // Non-paginated response
            warehouseProducts.value = response.data.data;
            pagination.value = {
                current_page: 1,
                total: response.data.data.length,
                per_page: response.data.data.length,
                last_page: 1
            };
        } else {
            // Empty or invalid response
            warehouseProducts.value = [];
            pagination.value = {
                current_page: 1,
                total: 0,
                per_page: 10,
                last_page: 1
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
            last_page: 1
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

watch(filters, () => {
    debouncedSearch();
}, { deep: true });

const openProductModal = (product) => {
    selectedProduct.value = product;
    showProductModal.value = true;
};

const openEditPriceModal = (product) => {
    selectedProduct.value = product;
    editForm.value = {
        price: product.price,
        critical_level_qty: product.critical_level_qty || 0
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
        critical_level_qty: 0
    };
};

const savePrice = async () => {
    try {
        isLoading.value = true;
        await axios.put(`/api/warehouse-products/${selectedProduct.value.id}`, editForm.value);
        toast.success('Product details updated successfully');
        await loadWarehouseProducts();
        closeEditPriceModal();
    } catch (error) {
        console.error('Error updating product:', error);
        toast.error(error.response?.data?.message || 'Failed to update product');
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    loadWarehouseProducts();
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
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />

                <!-- Warehouse Products Section -->
                <div class="py-6">
                    <div class="px-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Warehouse Products
                        </h3>
                        <div class="border rounded-lg overflow-hidden">
                            <!-- Filters -->
                            <div class="bg-white p-4 border-b border-gray-200 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Product</label>
                                        <input
                                            type="text"
                                            v-model="filters.product"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Search product..."
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Variation</label>
                                        <input
                                            type="text"
                                            v-model="filters.variation"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Search variation..."
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Stock</label>
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
                                        <label class="block text-sm font-medium text-gray-700">Price</label>
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
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Variation
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Stock
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Selling Price
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Last Cost
                                            </th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="product in warehouseProducts" :key="product.id" class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10" v-if="product.supplier_product_detail?.product?.avatar">
                                                        <img class="h-10 w-10 rounded-full object-cover" :src="product.supplier_product_detail.product.avatar" :alt="product.supplier_product_detail?.product?.name">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="font-medium text-gray-900 flex items-center">
                                                            {{ product.supplier_product_detail?.product?.name || '-' }}
                                                            <span v-if="product.has_serials" class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                                Serial/Batch
                                                            </span>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            SKU: {{ product.supplier_product_detail?.variation?.sku || '-' }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            Barcode: {{ product.supplier_product_detail?.variation?.barcode || '-' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="text-sm text-gray-900">
                                                    {{ product.supplier_product_detail?.variation?.name || '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="text-sm text-gray-900">
                                                    {{ formatNumber(product.qty, { minimumFractionDigits: 0 }) }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="text-sm text-gray-900">
                                                    {{ formatNumber(product.price, { style: 'currency', currency: 'PHP' }) }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="text-sm text-gray-900">
                                                    {{ formatNumber(product.last_cost, { style: 'currency', currency: 'PHP' }) }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex justify-center space-x-2">
                                                    <button
                                                        @click="openProductModal(product)"
                                                        class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50"
                                                        title="View Details"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="openEditPriceModal(product)"
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                        title="Edit Price"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="warehouseProducts.length === 0">
                                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                                                No products found in this warehouse
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <button
                                        @click="loadWarehouseProducts(pagination.current_page - 1)"
                                        :disabled="pagination.current_page === 1"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        @click="loadWarehouseProducts(pagination.current_page + 1)"
                                        :disabled="pagination.current_page === pagination.last_page"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
                                            to
                                            <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                                            of
                                            <span class="font-medium">{{ pagination.total }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <button
                                                v-for="page in pagination.last_page"
                                                :key="page"
                                                @click="loadWarehouseProducts(page)"
                                                :class="[
                                                    page === pagination.current_page
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
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
        <div v-if="showProductModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Product Details</h3>
                    <button @click="closeProductModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-6" v-if="selectedProduct">
                    <!-- Product Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Product Information</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-12 gap-6">
                                <!-- Left Column - Avatar -->
                                <div class="col-span-3">
                                    <div class="w-full aspect-square overflow-hidden rounded-lg bg-gray-100">
                                        <img 
                                            v-if="selectedProduct.supplier_product_detail?.product?.avatar"
                                            :src="selectedProduct.supplier_product_detail.product.avatar"
                                            :alt="selectedProduct.supplier_product_detail?.product?.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle Column - Information -->
                                <div class="col-span-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Name</p>
                                            <p class="mt-1">{{ selectedProduct.supplier_product_detail?.product?.name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">SKU</p>
                                            <p class="mt-1">{{ selectedProduct.supplier_product_detail?.variation?.sku }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Barcode</p>
                                            <p class="mt-1">{{ selectedProduct.supplier_product_detail?.variation?.barcode }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Unit of Measure</p>
                                            <p class="mt-1">{{ selectedProduct.supplier_product_detail?.product?.unit_of_measure }}</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-sm font-medium text-gray-500">Variation</p>
                                            <p class="mt-1">{{ selectedProduct.supplier_product_detail?.variation?.name || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column - QR Code -->
                                <div class="col-span-3 flex flex-col items-center justify-center">
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
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Warehouse Information</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">In Stock</p>
                                    <p class="mt-1">{{ formatNumber(selectedProduct.qty, { minimumFractionDigits: 0 }) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Selling Price</p>
                                    <p class="mt-1">{{ formatNumber(selectedProduct.price, { style: 'currency', currency: 'PHP' }) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Last Cost</p>
                                    <p class="mt-1">{{ formatNumber(selectedProduct.last_cost, { style: 'currency', currency: 'PHP' }) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Average Cost</p>
                                    <p class="mt-1">{{ formatNumber(selectedProduct.average_cost, { style: 'currency', currency: 'PHP' }) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Serial/Batch Numbers if applicable -->
                    <div v-if="selectedProduct.has_serials">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Serial/Batch Numbers</h4>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Serial Number</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Batch Number</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Manufactured Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="serial in selectedProduct.serials" :key="serial.id" class="hover:bg-gray-50">
                                        <td class="px-3 py-2">{{ serial.serial_number || '-' }}</td>
                                        <td class="px-3 py-2">{{ serial.batch_number || '-' }}</td>
                                        <td class="px-3 py-2">{{ serial.manufactured_at ? moment(serial.manufactured_at).format('MMM D, YYYY') : '-' }}</td>
                                        <td class="px-3 py-2">{{ serial.expired_at ? moment(serial.expired_at).format('MMM D, YYYY') : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transfer History -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Transfer History</h4>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">GR #</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transfer in selectedProduct.transfers" :key="transfer.id" class="hover:bg-gray-50">
                                        <td class="px-3 py-2">{{ moment(transfer.created_at).format('MMM D, YYYY') }}</td>
                                        <td class="px-3 py-2">{{ transfer.origin_warehouse?.name || '-' }}</td>
                                        <td class="px-3 py-2">{{ transfer.destination_warehouse?.name || '-' }}</td>
                                        <td class="px-3 py-2">{{ transfer.created_by_user?.name || '-' }}</td>
                                        <td class="px-3 py-2">
                                            <template v-if="transfer.goods_receipt_id">
                                                <Link 
                                                    :href="getGoodsReceiptUrl(transfer.goods_receipt_id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    {{ transfer.goods_receipt?.number || transfer.goods_receipt_id }}
                                                </Link>
                                            </template>
                                            <template v-else>-</template>
                                        </td>
                                        <td class="px-3 py-2">{{ transfer.notes || '-' }}</td>
                                    </tr>
                                    <tr v-if="!selectedProduct.transfers?.length">
                                        <td colspan="6" class="px-3 py-4 text-center text-gray-500">
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
        <div v-if="showEditPriceModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Product Details</h3>
                    <button @click="closeEditPriceModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Selling Price</label>
                        <input 
                            type="number" 
                            v-model="editForm.price"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Critical Level Quantity</label>
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
    </AppLayout>
</template>
