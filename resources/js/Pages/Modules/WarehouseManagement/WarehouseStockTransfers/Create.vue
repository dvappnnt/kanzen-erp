<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import Modal from "@/Components/Modal.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "warehouse-stock-transfers";
const isSubmitting = ref(false);
const toast = useToast();
const currentStep = ref(1);

const step1Data = ref({
    origin_warehouse_id: "",
    destination_warehouse_id: "",
});

const warehouses = ref([]);
const originWarehouse = ref(null);
const destinationWarehouse = ref(null);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const products = ref([]); // All products in origin warehouse
const selectedItems = ref([]); // [{ product, qty, serials: [] }]
const showSerialModal = ref(false);
const serialModalItem = ref(null);
const serialForm = ref({
    qty: 1,
    serials: [],
    type: "serial_numbers",
});
const bulkManufacturedDate = ref("");
const bulkExpiryDate = ref("");

const loadWarehouses = async () => {
    // Optionally preload warehouses if needed
};

const handleOriginWarehouseSelect = (response) => {
    if (response?.data?.[0]) {
        step1Data.value.origin_warehouse_id = response.data[0].id;
        originWarehouse.value = response.data[0];
        products.value = [];
        selectedItems.value = [];
    }
};
const handleDestinationWarehouseSelect = (response) => {
    if (response?.data?.[0]) {
        step1Data.value.destination_warehouse_id = response.data[0].id;
        destinationWarehouse.value = response.data[0];
    }
};

const canProceedStep1 = computed(() => {
    return (
        step1Data.value.origin_warehouse_id &&
        step1Data.value.destination_warehouse_id &&
        step1Data.value.origin_warehouse_id !== step1Data.value.destination_warehouse_id
    );
});

const nextStep = async () => {
    if (currentStep.value === 1) {
        if (!canProceedStep1.value) {
            toast.error("Please select different origin and destination warehouses.");
            return;
        }
        // Load products from origin warehouse
        try {
            const res = await axios.get(`/api/warehouse-products?warehouse_id=${step1Data.value.origin_warehouse_id}`);
            products.value = res.data.data || [];
        } catch (e) {
            toast.error("Failed to load products from origin warehouse.");
            return;
        }
        currentStep.value = 2;
    }
};
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const openSerialModal = (item) => {
    serialModalItem.value = item;
    serialForm.value.qty = item.qty || 1;
    serialForm.value.serials = item.serials ? [...item.serials] : [];
    serialForm.value.type = "serial_numbers";
    showSerialModal.value = true;
};
const closeSerialModal = () => {
    showSerialModal.value = false;
    serialModalItem.value = null;
    serialForm.value.serials = [];
};
const applyBulkDates = () => {
    serialForm.value.serials = serialForm.value.serials.map((serial) => ({
        ...serial,
        manufactured_at: bulkManufacturedDate.value || serial.manufactured_at,
        expired_at: bulkExpiryDate.value || serial.expired_at,
    }));
};
const addSerialRow = () => {
    if (serialForm.value.serials.length < serialForm.value.qty) {
        serialForm.value.serials.push({
            serial_number: "",
            batch_number: "",
            manufactured_at: bulkManufacturedDate.value || "",
            expired_at: bulkExpiryDate.value || "",
        });
    }
};
const removeSerialRow = (idx) => {
    serialForm.value.serials.splice(idx, 1);
};
const saveSerialsToItem = () => {
    if (serialForm.value.serials.length !== serialForm.value.qty) {
        toast.error("Serials count must match quantity.");
        return;
    }
    serialModalItem.value.serials = [...serialForm.value.serials];
    closeSerialModal();
};

const addItem = (product) => {
    if (selectedItems.value.find((i) => i.product.id === product.id)) {
        toast.error("Product already added.");
        return;
    }
    selectedItems.value.push({
        product,
        qty: 1,
        serials: product.has_serials ? [] : null,
    });
};
const removeItem = (idx) => {
    selectedItems.value.splice(idx, 1);
};

const finalizeTransfer = async () => {
    if (isSubmitting.value) return;
    // Validate all items
    for (const item of selectedItems.value) {
        if (!item.qty || item.qty < 1) {
            toast.error("All items must have a quantity of at least 1.");
            return;
        }
        if (item.product.has_serials && (!item.serials || item.serials.length !== item.qty)) {
            toast.error(`Serials required for ${item.product.supplier_product_detail.product.name}`);
            return;
        }
    }
    isSubmitting.value = true;
    try {
        // Prepare payload
        const details = selectedItems.value.map((item) => ({
            origin_warehouse_product_id: item.product.id,
            qty: item.qty,
            serials: item.product.has_serials ? item.serials : [],
        }));
        const payload = {
            origin_warehouse_id: step1Data.value.origin_warehouse_id,
            destination_warehouse_id: step1Data.value.destination_warehouse_id,
            details,
        };
        const res = await axios.post("/api/warehouse-stock-transfers", payload);
        toast.success("Stock transfer created successfully");
        router.get(`/${modelName}/${res.data.data.id}`);
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to create stock transfer");
    } finally {
        isSubmitting.value = false;
    }
};

// ...template below...
</script>

<template>
    <AppLayout :title="`Create ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create {{ singularizeAndFormat(modelName) }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stepper -->
            <div class="mb-8">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="relative">
                        <div class="absolute top-5 left-0 w-full h-1 bg-gray-200">
                            <div class="h-1 transition-all duration-300" :style="{ 'background-color': buttonPrimaryBgColor, width: `${(currentStep - 1) * 100}%` }"></div>
                        </div>
                        <div class="relative flex justify-between">
                            <div v-for="(step, index) in ['Select Warehouses', 'Review Stock Transfer']" :key="index" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium relative bg-white border-2 transition-all duration-300"
                                    :style="{
                                        'border-color': index + 1 <= currentStep ? buttonPrimaryBgColor : 'rgb(209 213 219)',
                                        'background-color': index + 1 < currentStep ? buttonPrimaryBgColor : 'white',
                                        color: index + 1 < currentStep ? buttonPrimaryTextColor : index + 1 === currentStep ? buttonPrimaryBgColor : 'rgb(107 114 128)',
                                    }">
                                    <span v-if="index + 1 < currentStep">✓</span>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="mt-2 text-sm text-center transition-all duration-300"
                                    :style="{
                                        color: index + 1 === currentStep ? buttonPrimaryBgColor : index + 1 < currentStep ? 'rgb(17 24 39)' : 'rgb(107 114 128)',
                                        'font-weight': index + 1 === currentStep ? '500' : '400',
                                    }">
                                    {{ step }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Step 1: Select Warehouses -->
            <div v-if="currentStep === 1" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin Warehouse <span class="text-red-500">*</span></label>
                        <Autocomplete search-url="/api/autocomplete/warehouses" placeholder="Search for origin warehouse..." model-name="warehouses" @select="handleOriginWarehouseSelect" class="w-full" />
                        <div v-if="originWarehouse" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                            <span class="font-medium">Selected:</span> {{ originWarehouse.name }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Destination Warehouse <span class="text-red-500">*</span></label>
                        <Autocomplete search-url="/api/autocomplete/warehouses" placeholder="Search for destination warehouse..." model-name="warehouses" @select="handleDestinationWarehouseSelect" class="w-full" />
                        <div v-if="destinationWarehouse" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                            <span class="font-medium">Selected:</span> {{ destinationWarehouse.name }}
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="nextStep" class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white" :class="{ 'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80': true }" :style="{ '--primary-color': buttonPrimaryBgColor }" :disabled="!canProceedStep1">
                        Next
                    </button>
                </div>
            </div>
            <!-- Step 2: Review Stock Transfer -->
            <div v-if="currentStep === 2" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Products in Origin Warehouse</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty in Stock</th>
                                    <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transfer Qty</th>
                                    <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serials</th>
                                    <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="product in products" :key="product.id">
                                    <td class="px-2 py-2">{{ product.supplier_product_detail.product.name }}</td>
                                    <td class="px-2 py-2">{{ product.qty }}</td>
                                    <td class="px-2 py-2">
                                        <input type="number" min="1" :max="product.qty" v-model.number="(selectedItems.find(i => i.product.id === product.id) || {}).qty" @input="() => { if (!selectedItems.find(i => i.product.id === product.id)) addItem(product); }" class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <button v-if="product.has_serials && selectedItems.find(i => i.product.id === product.id)" @click="openSerialModal(selectedItems.find(i => i.product.id === product.id))" class="text-blue-600 hover:underline">Edit Serials</button>
                                        <span v-else-if="product.has_serials">—</span>
                                        <span v-else>n/a</span>
                                    </td>
                                    <td class="px-2 py-2">
                                        <button v-if="selectedItems.find(i => i.product.id === product.id)" @click="removeItem(selectedItems.findIndex(i => i.product.id === product.id))" class="text-red-600 hover:underline">Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Review Transfer Items</h3>
                    <ul>
                        <li v-for="item in selectedItems" :key="item.product.id" class="mb-2">
                            <span class="font-semibold">{{ item.product.supplier_product_detail.product.name }}</span> — Qty: {{ item.qty }}
                            <span v-if="item.product.has_serials"> | Serials: {{ item.serials ? item.serials.length : 0 }}</span>
                        </li>
                    </ul>
                </div>
                <div class="mt-6 flex justify-between">
                    <button @click="prevStep" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                    <button @click="finalizeTransfer" class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" :disabled="isSubmitting">
                        <span v-if="isSubmitting" class="animate-spin mr-2">⌛</span>
                        {{ isSubmitting ? "Creating..." : "Create Stock Transfer" }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Serial Modal -->
        <Modal :show="showSerialModal" @close="closeSerialModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Input Serials</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" v-model.number="serialForm.qty" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="flex gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bulk Manufactured Date</label>
                            <input type="date" v-model="bulkManufacturedDate" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bulk Expiry Date</label>
                            <input type="date" v-model="bulkExpiryDate" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        </div>
                    </div>
                    <button @click="applyBulkDates" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" type="button">Apply Bulk Dates</button>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch Number</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufactured Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(serial, idx) in serialForm.serials" :key="idx">
                                    <td class="px-3 py-2"><input type="text" v-model="serial.serial_number" class="block w-full border-0 p-0 focus:ring-0 sm:text-sm" placeholder="Serial number" /></td>
                                    <td class="px-3 py-2"><input type="text" v-model="serial.batch_number" class="block w-full border-0 p-0 focus:ring-0 sm:text-sm" placeholder="Batch number" /></td>
                                    <td class="px-3 py-2"><input type="date" v-model="serial.manufactured_at" class="block w-full border-0 p-0 focus:ring-0 sm:text-sm" /></td>
                                    <td class="px-3 py-2"><input type="date" v-model="serial.expired_at" class="block w-full border-0 p-0 focus:ring-0 sm:text-sm" /></td>
                                    <td class="px-3 py-2 text-right"><button @click="removeSerialRow(idx)" class="text-red-600 hover:text-red-900">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button @click="addSerialRow" class="mt-2 text-sm text-indigo-600 hover:text-indigo-900 flex items-center">+ Add Serial</button>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="closeSerialModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button @click="saveSerialsToItem" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">Save Serials</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
