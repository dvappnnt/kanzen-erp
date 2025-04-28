<template>
    <AppLayout title="Create Purchase Requisition">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Purchase Requisition
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Step Progress -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div v-for="(step, index) in steps" :key="index"
                                class="flex items-center"
                                :class="{'text-primary-600': currentStep >= index + 1, 'text-gray-400': currentStep < index + 1}">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                                    :class="{'border-primary-600 bg-primary-600 text-white': currentStep >= index + 1, 'border-gray-400': currentStep < index + 1}">
                                    {{ index + 1 }}
                                </div>
                                <span class="ml-2">{{ step }}</span>
                                <div v-if="index < steps.length - 1" class="w-24 h-1 mx-4"
                                    :class="{'bg-primary-600': currentStep > index + 1, 'bg-gray-300': currentStep <= index + 1}"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: Select Company -->
                    <div v-if="currentStep === 1">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Select Company</label>
                            <Combobox v-model="selectedCompany" :options="companies" 
                                display-value="name" 
                                @update:modelValue="handleCompanySelect"
                                class="mt-1" />
                        </div>
                        <div class="flex justify-end">
                            <PrimaryButton @click="nextStep" :disabled="!selectedCompany">
                                Next
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Step 2: Select Warehouse -->
                    <div v-if="currentStep === 2">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Select Warehouse</label>
                            <Combobox v-model="selectedWarehouse" :options="warehouses" 
                                display-value="name"
                                @update:modelValue="handleWarehouseSelect"
                                class="mt-1" />
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="previousStep">
                                Back
                            </SecondaryButton>
                            <PrimaryButton @click="nextStep" :disabled="!selectedWarehouse">
                                Next
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Step 3: Add Products -->
                    <div v-if="currentStep === 3">
                        <!-- Product Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Search Products</label>
                            <Combobox v-model="selectedProduct" :options="products" 
                                display-value="name"
                                @update:modelValue="handleProductSelect"
                                class="mt-1" />
                        </div>

                        <!-- Product Cart -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900">Selected Products</h3>
                            <div class="mt-4">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in cartItems" :key="item.product.id">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ item.product.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" v-model="item.quantity" min="1"
                                                    class="w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                                    @input="updateItemTotal(item)" />
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(item.unit_price) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(item.total) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <button @click="removeFromCart(item)" 
                                                    class="text-red-600 hover:text-red-900">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-right font-medium">Total:</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ formatCurrency(cartTotal) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea v-model="notes" rows="3" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"></textarea>
                        </div>

                        <div class="flex justify-between mt-6">
                            <SecondaryButton @click="previousStep">
                                Back
                            </SecondaryButton>
                            <PrimaryButton @click="submitForm" :disabled="!cartItems.length">
                                Create Requisition
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Combobox from '@/Components/Combobox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const steps = ['Select Company', 'Select Warehouse', 'Add Products'];
const currentStep = ref(1);
const selectedCompany = ref(null);
const selectedWarehouse = ref(null);
const selectedProduct = ref(null);
const cartItems = ref([]);
const notes = ref('');

const companies = ref([]);
const warehouses = ref([]);
const products = ref([]);

const cartTotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.total, 0);
});

const form = useForm({
    company_id: null,
    warehouse_id: null,
    items: [],
    notes: '',
});

const nextStep = () => {
    if (currentStep.value < steps.length) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const handleCompanySelect = async (company) => {
    if (company) {
        form.company_id = company.id;
        // Fetch warehouses for selected company
        const response = await axios.get(route('api.warehouses.autocomplete', { company_id: company.id }));
        warehouses.value = response.data;
    }
};

const handleWarehouseSelect = (warehouse) => {
    if (warehouse) {
        form.warehouse_id = warehouse.id;
    }
};

const handleProductSelect = (product) => {
    if (product && !cartItems.value.find(item => item.product.id === product.id)) {
        cartItems.value.push({
            product: product,
            quantity: 1,
            unit_price: product.price || 0,
            total: product.price || 0
        });
    }
    selectedProduct.value = null;
};

const updateItemTotal = (item) => {
    item.total = item.quantity * item.unit_price;
};

const removeFromCart = (item) => {
    const index = cartItems.value.indexOf(item);
    if (index > -1) {
        cartItems.value.splice(index, 1);
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
};

const submitForm = () => {
    form.items = cartItems.value.map(item => ({
        product_id: item.product.id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        total_price: item.total
    }));
    form.notes = notes.value;

    form.post(route('purchase-requisitions.store'), {
        onSuccess: () => {
            // Handle success
        }
    });
};

// Fetch initial data
onMounted(async () => {
    const response = await axios.get(route('api.companies.autocomplete'));
    companies.value = response.data;
});
</script> 