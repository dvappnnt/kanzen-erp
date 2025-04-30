<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useToast } from "vue-toastification";
import { singularizeAndFormat, formatDate } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import Modal from "@/Components/Modal.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "purchase-orders";
const page = usePage();
const isSubmitting = ref(false);
const toast = useToast();
const currentStep = ref(1);
const purchaseOrderId = ref(null);

// Form data for each step
const step1Data = ref({
    company_id: "",
    supplier_id: "",
    warehouse_id: "",
});

const step2Data = ref({
    order_date: new Date().toISOString().split("T")[0],
    expected_delivery_date: "",
    payment_terms: "",
    shipping_terms: "",
    notes: "",
});

const step3Data = ref({
    items: [],
});

// Available options for dropdowns
const companies = ref([]);
const suppliers = ref([]);
const warehouses = ref([]);

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

// Add these refs after the other refs
const showAddItemModal = ref(false);
const supplierProducts = ref([]);
const selectedProduct = ref(null);
const itemForm = ref({
    supplier_product_variation_id: "",
    quantity: 1,
    unit_price: 0,
    notes: "",
});

// Replace the loadDropdownData and search functions with these mapping functions
const mapCompanyData = (data) => ({
    id: data.id,
    name: data.name,
    // Add any other fields you need
});

const mapSupplierData = (data) => ({
    id: data.id,
    name: data.name,
    // Add any other fields you need
});

const mapWarehouseData = (data) => ({
    id: data.id,
    name: data.name,
    // Add any other fields you need
});

// Handle selection functions
const handleCompanySelect = (response) => {
    if (response?.data?.[0]) {
        step1Data.value.company_id = response.data[0].id;
        companies.value = [response.data[0]];
    }
};

const handleSupplierSelect = (response) => {
    if (response?.data?.[0]) {
        step1Data.value.supplier_id = response.data[0].id;
        suppliers.value = [response.data[0]];
    }
};

const handleWarehouseSelect = (response) => {
    if (response?.data?.[0]) {
        step1Data.value.warehouse_id = response.data[0].id;
        warehouses.value = [response.data[0]];
    }
};

// Handle step navigation
const nextStep = async () => {
    if (currentStep.value === 1) {
        if (
            !step1Data.value.company_id ||
            !step1Data.value.supplier_id ||
            !step1Data.value.warehouse_id
        ) {
            toast.error("Please fill in all required fields");
            return;
        }
        currentStep.value = 2;
    } else if (currentStep.value === 2) {
        // Validate required fields
        if (!step2Data.value.order_date) {
            toast.error("Order date is required");
            return;
        }

        // Ensure dates are in proper format
        try {
            // Format dates for API
            if (step2Data.value.order_date) {
                const orderDate = new Date(step2Data.value.order_date);
                if (isNaN(orderDate.getTime())) {
                    toast.error("Invalid order date format");
                    return;
                }
            }

            if (step2Data.value.expected_delivery_date) {
                const deliveryDate = new Date(
                    step2Data.value.expected_delivery_date
                );
                if (isNaN(deliveryDate.getTime())) {
                    toast.error("Invalid expected delivery date format");
                    return;
                }
            }

            currentStep.value = 3;
        } catch (error) {
            console.error("Date validation error:", error);
            toast.error("Please check the date formats");
            return;
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

// Load initial data when component mounts
onMounted(() => {
    // Remove the old loadDropdownData function since we don't need it anymore
});

// Add these functions before the template
const openAddItemModal = () => {
    loadSupplierProducts();
    showAddItemModal.value = true;
};

const closeAddItemModal = () => {
    showAddItemModal.value = false;
    itemForm.value = {
        supplier_product_variation_id: "",
        quantity: 1,
        unit_price: 0,
        notes: "",
    };
    selectedProduct.value = null;
};

const loadSupplierProducts = async () => {
    try {
        const response = await axios.get(
            `/api/suppliers/${step1Data.value.supplier_id}/products`
        );
        supplierProducts.value = response.data.data;
    } catch (error) {
        console.error("Error loading supplier products:", error);
        toast.error("Failed to load supplier products");
    }
};

const handleProductSelect = (productId) => {
    const product = supplierProducts.value.find((p) => p.id === productId);
    if (product) {
        selectedProduct.value = product;
        itemForm.value.unit_price = product.price || 0;
    }
};

const addItem = () => {
    if (!selectedProduct.value || !itemForm.value.quantity) {
        toast.error("Please select a product and specify quantity");
        return;
    }

    step3Data.items.push({
        supplier_product_variation_id: selectedProduct.value.id,
        product_name: selectedProduct.value.name,
        quantity: itemForm.value.quantity,
        unit_price: itemForm.value.unit_price,
        notes: itemForm.value.notes,
        total: itemForm.value.quantity * itemForm.value.unit_price,
    });

    closeAddItemModal();
};

const removeItem = (index) => {
    step3Data.items.splice(index, 1);
};

// Add payment and shipping terms options
const paymentTermsOptions = [
    { value: "immediate", label: "Immediate Payment" },
    { value: "net_15", label: "Net 15 Days" },
    { value: "net_30", label: "Net 30 Days" },
    { value: "net_45", label: "Net 45 Days" },
    { value: "net_60", label: "Net 60 Days" },
    { value: "eom", label: "End of Month" },
    { value: "cod", label: "Cash on Delivery" },
];

const shippingTermsOptions = [
    { value: "ex_works", label: "EX Works (EXW)" },
    { value: "fob", label: "Free on Board (FOB)" },
    { value: "cif", label: "Cost, Insurance & Freight (CIF)" },
    { value: "dap", label: "Delivered at Place (DAP)" },
    { value: "ddp", label: "Delivered Duty Paid (DDP)" },
];

const finalizePO = async () => {
    if (isSubmitting.value) return;

    try {
        isSubmitting.value = true;

        const formData = {
            company_id: step1Data.value.company_id,
            supplier_id: step1Data.value.supplier_id,
            warehouse_id: step1Data.value.warehouse_id,
            order_date: step2Data.value.order_date,
            expected_delivery_date: step2Data.value.expected_delivery_date,
            payment_terms: step2Data.value.payment_terms,
            shipping_terms: step2Data.value.shipping_terms,
            notes: step2Data.value.notes,
        };

        await axios.post("/api/purchase-orders", formData);
        toast.success("Purchase order created successfully");

        // Use router.visit with replace option to prevent back navigation
        try {
            const response = await axios.post("/api/purchase-orders", formData);
            // toast.success("Purchase order created successfully");

            const modelDataId = response.data.modelData.id;
            router.get(`/purchase-orders/${modelDataId}`);
        } catch (error) {
            console.error(error);
            toast.error("Failed to create purchase order");
        } finally {
            isSubmitting.value = false;
        }
    } catch (error) {
        console.error("Error creating purchase order:", error);
        toast.error(
            error.response?.data?.message || "Failed to create purchase order"
        );
        isSubmitting.value = false;
    }
};
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
            <!-- Step Progress -->
            <div class="mb-8">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="relative">
                        <!-- Progress Line -->
                        <div
                            class="absolute top-5 left-0 w-full h-1 bg-gray-200"
                        >
                            <div
                                class="h-1 transition-all duration-300"
                                :style="{
                                    'background-color': buttonPrimaryBgColor,
                                    width: `${((currentStep - 1) / 2) * 100}%`,
                                }"
                            ></div>
                        </div>

                        <!-- Steps -->
                        <div class="relative flex justify-between">
                            <div
                                v-for="(step, index) in [
                                    'Select Company & Supplier',
                                    'Basic Details',
                                    'Review Order',
                                ]"
                                :key="index"
                                class="flex flex-col items-center"
                            >
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium relative bg-white border-2 transition-all duration-300"
                                    :style="{
                                        'border-color':
                                            index + 1 <= currentStep
                                                ? buttonPrimaryBgColor
                                                : 'rgb(209 213 219)',
                                        'background-color':
                                            index + 1 < currentStep
                                                ? buttonPrimaryBgColor
                                                : 'white',
                                        color:
                                            index + 1 < currentStep
                                                ? buttonPrimaryTextColor
                                                : index + 1 === currentStep
                                                ? buttonPrimaryBgColor
                                                : 'rgb(107 114 128)',
                                    }"
                                >
                                    <span v-if="index + 1 < currentStep"
                                        >✓</span
                                    >
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div
                                    class="mt-2 text-sm text-center transition-all duration-300"
                                    :style="{
                                        color:
                                            index + 1 === currentStep
                                                ? buttonPrimaryBgColor
                                                : index + 1 < currentStep
                                                ? 'rgb(17 24 39)'
                                                : 'rgb(107 114 128)',
                                        'font-weight':
                                            index + 1 === currentStep
                                                ? '500'
                                                : '400',
                                    }"
                                >
                                    {{ step }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Company, Supplier, Warehouse Selection -->
            <div
                v-if="currentStep === 1"
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
            >
                <div class="space-y-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Company <span class="text-red-500">*</span></label
                        >
                        <Autocomplete
                            search-url="/api/autocomplete/companies"
                            placeholder="Search for a company..."
                            model-name="companies"
                            :map-custom-buttons="mapCompanyData"
                            @select="handleCompanySelect"
                            class="w-full"
                        />
                        <div
                            v-if="step1Data.company_id"
                            class="mt-2 p-2 bg-gray-50 rounded text-sm"
                        >
                            <span class="font-medium">Selected:</span>
                            {{ companies[0]?.name }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Supplier <span class="text-red-500">*</span></label
                        >
                        <Autocomplete
                            search-url="/api/autocomplete/suppliers"
                            placeholder="Search for a supplier..."
                            model-name="suppliers"
                            :map-custom-buttons="mapSupplierData"
                            @select="handleSupplierSelect"
                            class="w-full"
                        />
                        <div
                            v-if="step1Data.supplier_id"
                            class="mt-2 p-2 bg-gray-50 rounded text-sm"
                        >
                            <span class="font-medium">Selected:</span>
                            {{ suppliers[0]?.name }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Warehouse
                            <span class="text-red-500">*</span></label
                        >
                        <Autocomplete
                            search-url="/api/autocomplete/warehouses"
                            placeholder="Search for a warehouse..."
                            model-name="warehouses"
                            :map-custom-buttons="mapWarehouseData"
                            @select="handleWarehouseSelect"
                            class="w-full"
                        />
                        <div
                            v-if="step1Data.warehouse_id"
                            class="mt-2 p-2 bg-gray-50 rounded text-sm"
                        >
                            <span class="font-medium">Selected:</span>
                            {{ warehouses[0]?.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Basic Details -->
            <div
                v-if="currentStep === 2"
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
            >
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Order Date
                            <span class="text-red-500">*</span></label
                        >
                        <input
                            type="date"
                            v-model="step2Data.order_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Expected Delivery Date</label
                        >
                        <input
                            type="date"
                            v-model="step2Data.expected_delivery_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Payment Terms</label
                        >
                        <select
                            v-model="step2Data.payment_terms"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Payment Terms</option>
                            <option
                                v-for="option in paymentTermsOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Shipping Terms</label
                        >
                        <select
                            v-model="step2Data.shipping_terms"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Shipping Terms</option>
                            <option
                                v-for="option in shippingTermsOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Notes</label
                        >
                        <textarea
                            v-model="step2Data.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter any additional notes"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 3: Review Purchase Order -->
            <div
                v-if="currentStep === 3"
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
            >
                <div class="space-y-8">
                    <!-- Company, Supplier & Warehouse Details -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Selected Organization Details
                        </h3>
                        <div
                            class="bg-gray-50 rounded-lg p-4 grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Company
                                </p>
                                <p class="mt-1">{{ companies[0]?.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Supplier
                                </p>
                                <p class="mt-1">{{ suppliers[0]?.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Warehouse
                                </p>
                                <p class="mt-1">{{ warehouses[0]?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Order Details
                        </h3>
                        <div
                            class="bg-gray-50 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Order Date
                                </p>
                                <p class="mt-1">
                                    {{
                                        formatDate(
                                            "M d Y",
                                            step2Data.order_date
                                        )
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Expected Delivery Date
                                </p>
                                <p class="mt-1">
                                    {{
                                        step2Data.expected_delivery_date
                                            ? formatDate(
                                                  "M d Y",
                                                  step2Data.expected_delivery_date
                                              )
                                            : "Not specified"
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Payment Terms
                                </p>
                                <p class="mt-1">
                                    {{
                                        paymentTermsOptions.find(
                                            (opt) =>
                                                opt.value ===
                                                step2Data.payment_terms
                                        )?.label || "Not specified"
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Shipping Terms
                                </p>
                                <p class="mt-1">
                                    {{
                                        shippingTermsOptions.find(
                                            (opt) =>
                                                opt.value ===
                                                step2Data.shipping_terms
                                        )?.label || "Not specified"
                                    }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm font-medium text-gray-500">
                                    Notes
                                </p>
                                <p class="mt-1">
                                    {{ step2Data.notes || "No notes added" }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-6 flex justify-between">
                <button
                    v-if="currentStep > 1"
                    @click="prevStep"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Previous
                </button>
                <button
                    v-if="currentStep < 3"
                    @click="nextStep"
                    class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white"
                    :class="{
                        'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80': true,
                    }"
                    :style="{
                        '--primary-color': buttonPrimaryBgColor,
                    }"
                    :disabled="isSubmitting"
                >
                    <span v-if="isSubmitting" class="animate-spin mr-2"
                        >⌛</span
                    >
                    {{ currentStep === 2 ? "Continue to Review" : "Next" }}
                </button>
                <button
                    v-else
                    type="button"
                    @click="finalizePO"
                    class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    :disabled="isSubmitting"
                >
                    <span v-if="isSubmitting" class="animate-spin mr-2"
                        >⌛</span
                    >
                    {{ isSubmitting ? "Creating..." : "Create Purchase Order" }}
                </button>
            </div>
        </div>

        <!-- Add Item Modal -->
        <Modal :show="showAddItemModal" @close="closeAddItemModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Add Item</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Product <span class="text-red-500">*</span></label
                        >
                        <select
                            v-model="itemForm.supplier_product_variation_id"
                            @change="handleProductSelect($event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Product</option>
                            <option
                                v-for="product in supplierProducts"
                                :key="product.id"
                                :value="product.id"
                            >
                                {{ product.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Quantity <span class="text-red-500">*</span></label
                        >
                        <input
                            type="number"
                            v-model="itemForm.quantity"
                            min="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Unit Price</label
                        >
                        <input
                            type="number"
                            v-model="itemForm.unit_price"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Notes</label
                        >
                        <textarea
                            v-model="itemForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Any additional notes about this item"
                        ></textarea>
                    </div>

                    <div
                        v-if="selectedProduct"
                        class="bg-gray-50 p-4 rounded-md"
                    >
                        <h3 class="text-sm font-medium text-gray-700 mb-2">
                            Product Details
                        </h3>
                        <div class="text-sm text-gray-600">
                            <p>
                                <span class="font-medium">Price:</span>
                                {{ selectedProduct.price }}
                            </p>
                            <p v-if="selectedProduct.description">
                                <span class="font-medium">Description:</span>
                                {{ selectedProduct.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeAddItemModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="addItem"
                        class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white"
                        :class="{
                            'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80': true,
                        }"
                        :style="{
                            '--primary-color': buttonPrimaryBgColor,
                        }"
                    >
                        Add Item
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
