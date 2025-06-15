<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { useToast } from "vue-toastification";
import { useColors } from "@/Composables/useColors";
import { Link } from "@inertiajs/vue3";
import axios from "@/axios";
import { formatNumber } from "@/utils/global";
import moment from "moment";
import { debounce } from "lodash";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Dropzone from "@/Components/Form/Dropzone.vue";

const page = usePage();
const toast = useToast();
const isSubmitting = ref(false);

// Form Data
const formData = ref({
    company_id: null,
    customer_id: null,
    warehouse_id: null,
    type: 'sales-invoice',
    invoice_date: new Date().toISOString().split('T')[0],
    discount_type: 'percentage',
    discount_rate: 0,
    discount_amount: 0,
    tax_rate: 12,
    tax_amount: 0,
    shipping_cost: 0,
    subtotal: 0,
    total_amount: 0,
    currency: 'PHP',
    status: 'draft',
    is_credit: false,
    payment_method: '',
    payment_details: {
        account_number: '',
        account_name: '',
        bank_id: null,
        company_account_id: null,
        reference_number: null,
        receipt_attachment: null,
        status: 'pending',
        payment_date: null,
        amount: 0
    },
    items: []
});

// Selected entities
const selectedCompany = ref(null);
const selectedWarehouse = ref(null);
const selectedCustomer = ref(null);
const selectedProduct = ref(null);
const showSerialModal = ref(false);
const currentItem = ref(null);
const serialNumbers = ref([]);
const currentProduct = ref(null);
const serialNumber = ref('');
const serialError = ref('');

// Available options
const warehouses = ref([]);
const customers = ref([]);
const products = ref([]);
const paymentMethods = ref([
    { id: 'cash', name: 'Cash' },
    { id: 'bank-transfer', name: 'Bank Transfer' },
    { id: 'credit-card', name: 'Credit Card' },
    { id: 'gcash', name: 'GCash' }
]);

// Payment method related refs
const showPaymentModal = ref(false);
const selectedBank = ref(null);
const selectedCompanyAccount = ref(null);
const bankSearch = ref('');
const bankResults = ref([]);
const showBankResults = ref(false);
const companyAccountSearch = ref('');
const companyAccountResults = ref([]);
const showCompanyAccountResults = ref(false);

// Product search and autocomplete
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchResults = ref(false);

// Serial List Modal
const showSerialListModalFlag = ref(false);
const selectedItem = ref(null);

// Watch for company selection to load related warehouses and customers
watch(() => selectedCompany.value, async (company) => {
    if (company) {
        formData.value.company_id = company.id;
        // Reset dependent selections
        selectedWarehouse.value = null;
        selectedCustomer.value = null;
        formData.value.warehouse_id = null;
        formData.value.customer_id = null;
        warehouses.value = [];
        customers.value = [];
        products.value = [];
        formData.value.items = [];

        // Load warehouses and customers for this company
        try {
            const [warehousesResponse, customersResponse] = await Promise.all([
                axios.get('/api/warehouses', { params: { company_id: company.id } }),
                axios.get('/api/customers', { params: { company_id: company.id } })
            ]);
            warehouses.value = warehousesResponse.data.data;
            customers.value = customersResponse.data.data;
        } catch (error) {
            console.error('Error loading company data:', error);
            toast.error('Failed to load company data');
        }
    }
});

// Watch for warehouse selection to load products
watch(() => selectedWarehouse.value, async (warehouse) => {
    if (warehouse) {
        formData.value.warehouse_id = warehouse.id;
        products.value = [];
        formData.value.items = [];
        await loadWarehouseProducts();
    }
});

// Watch for customer selection
watch(() => selectedCustomer.value, (customer) => {
    if (customer) {
        formData.value.customer_id = customer.id;
    }
});

const loadWarehouseProducts = async () => {
    if (!selectedWarehouse.value) return;
    try {
        const response = await axios.get('/api/search/warehouse-products', {
            params: { warehouse_id: selectedWarehouse.value.id }
        });
        products.value = response.data.data;
    } catch (error) {
        console.error('Error loading warehouse products:', error);
        toast.error('Failed to load products');
    }
};

// Computed properties for calculations
const subtotal = computed(() => {
    return formData.value.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
});

const taxAmount = computed(() => {
    return (subtotal.value * formData.value.tax_rate) / 100;
});

const discountAmount = computed(() => {
    if (formData.value.discount_type === 'percentage') {
        return (subtotal.value * formData.value.discount_rate) / 100;
    }
    return formData.value.discount_rate;
});

const totalAmount = computed(() => {
    return subtotal.value + taxAmount.value - discountAmount.value + formData.value.shipping_cost;
});

// Watch for changes in computed values
watch([subtotal, taxAmount, discountAmount, totalAmount], () => {
    formData.value.subtotal = subtotal.value;
    formData.value.tax_amount = taxAmount.value;
    formData.value.discount_amount = discountAmount.value;
    formData.value.total_amount = totalAmount.value;
    formData.value.payment_details.amount = totalAmount.value;
});

// Methods for handling items
const searchProducts = debounce(async () => {
    if (!searchQuery.value.trim() || !selectedWarehouse.value) return;
    
    try {
        const response = await axios.get('/api/search/warehouse-products', {
            params: {
                warehouse_id: selectedWarehouse.value.id,
                search: searchQuery.value
            }
        });
        searchResults.value = response.data.data;
        showSearchResults.value = true;
    } catch (error) {
        console.error('Error searching products:', error);
        toast.error('Failed to search products');
    }
}, 300);

const selectProduct = async (product) => {
    if (!product.supplier_product_detail?.product) {
        toast.error('Invalid product data');
        return;
    }

    try {
        // Check if product has serials
        const response = await axios.get('/api/search/warehouse-products', {
            params: {
                warehouse_id: selectedWarehouse.value.id,
                product_id: product.id,
                check_serials: true
            }
        });

        const productData = response.data.data.find(p => p.id === product.id);
        if (productData && productData.has_serials) {
            // If product has serials, show serial modal
            currentProduct.value = product;
            showSerialModal.value = true;
            serialNumber.value = '';
            serialError.value = '';
            return;
        }

        // If no serials, add directly to cart
        const existingItem = formData.value.items.find(item => item.warehouse_product_id === product.id);
        if (existingItem) {
            existingItem.qty += 1;
            existingItem.total = existingItem.price * existingItem.qty;
        } else {
            formData.value.items.push({
                warehouse_product_id: product.id,
                name: product.supplier_product_detail.product.name,
                price: parseFloat(product.price),
                qty: 1,
                total: parseFloat(product.price),
                has_serials: false,
                serials: []
            });
        }
        
        searchQuery.value = '';
        showSearchResults.value = false;

    } catch (error) {
        console.error('Error checking product serials:', error);
        toast.error('Failed to check product serials');
    }
};

const handleSerialSubmit = async () => {
    if (!serialNumber.value) {
        serialError.value = 'Please enter a serial number';
        return;
    }

    try {
        // Check if serial exists and is available using the correct endpoint
        const response = await axios.get('/api/serial-check/warehouse-products', {
            params: {
                warehouse_id: selectedWarehouse.value.id,
                product_id: currentProduct.value.id,
                serial_number: serialNumber.value
            }
        });

        if (!response.data.valid && !response.data.data) {
            serialError.value = response.data.message || 'Invalid or already sold serial number';
            return;
        }

        // Check if serial is already in cart
        const existingItem = formData.value.items.find(item => item.warehouse_product_id === currentProduct.value.id);
        if (existingItem) {
            if (existingItem.serials.includes(serialNumber.value)) {
                serialError.value = 'This serial number is already in your cart';
                return;
            }
            existingItem.serials.push(serialNumber.value);
            existingItem.qty += 1;
            existingItem.total = existingItem.price * existingItem.qty;
        } else {
            formData.value.items.push({
                warehouse_product_id: currentProduct.value.id,
                name: currentProduct.value.supplier_product_detail.product.name,
                price: parseFloat(currentProduct.value.price),
                qty: 1,
                total: parseFloat(currentProduct.value.price),
                has_serials: true,
                serials: [serialNumber.value]
            });
        }

        // Clear input but keep modal open for more serial entries
        serialNumber.value = '';
        serialError.value = '';
        
        // Focus back on the input for next serial
        nextTick(() => {
            if (serialInput.value) {
                serialInput.value.focus();
            }
        });

    } catch (error) {
        console.error('Error validating serial:', error);
        serialError.value = 'Failed to validate serial number';
    }
};

const closeSerialModal = () => {
    showSerialModal.value = false;
    currentProduct.value = null;
    serialNumber.value = '';
    serialError.value = '';
};

const updateItemQuantity = async (item, change) => {
    if (change > 0 && item.has_serials) {
        // For increment with serials, show serial modal
        currentProduct.value = {
            id: item.warehouse_product_id,
            supplier_product_detail: { product: { name: item.name } },
            price: item.price
        };
        showSerialModal.value = true;
        serialNumber.value = '';
        serialError.value = '';
        return;
    } else if (change < 0 && item.has_serials) {
        // For decrement with serials, remove last serial
        if (item.serials.length > 0) {
            item.serials.pop();
            item.qty -= 1;
            item.total = item.price * item.qty;
            if (item.qty === 0) {
                removeItem(item);
            }
        }
        return;
    }

    // For non-serial items
    const newQty = item.qty + change;
    if (newQty > 0) {
        item.qty = newQty;
        item.total = item.price * newQty;
    } else {
        removeItem(item);
    }
};

const removeItem = (item) => {
    const index = formData.value.items.findIndex(i => i.warehouse_product_id === item.warehouse_product_id);
    if (index > -1) {
        formData.value.items.splice(index, 1);
    }
};

// Add validation rules
const validateForm = () => {
    const errors = {};

    if (!formData.value.company_id) errors.company_id = 'Company is required';
    if (!formData.value.customer_id) errors.customer_id = 'Customer is required';
    if (!formData.value.warehouse_id) errors.warehouse_id = 'Warehouse is required';
    if (formData.value.items.length === 0) errors.items = 'At least one item is required';

    // Only validate payment details if not credit and is sales invoice
    if (!formData.value.is_credit && formData.value.type === 'sales-invoice') {
        if (!formData.value.payment_method) {
            errors.payment_method = 'Payment method is required';
        } else if (formData.value.payment_method !== 'cash') {
            // Reference number is required for all payment methods except cash
            if (!formData.value.payment_details.reference_number) {
                errors.reference_number = 'Reference number is required';
            }
        }
    }

    return errors;
};

// Form submission
const submitForm = async () => {
    try {
        const errors = validateForm();
        if (Object.keys(errors).length > 0) {
            Object.values(errors).forEach(error => toast.error(error));
            return;
        }

        isSubmitting.value = true;

        // Create base data object
        const submitData = {
            company_id: formData.value.company_id,
            customer_id: formData.value.customer_id,
            warehouse_id: formData.value.warehouse_id,
            type: formData.value.type,
            invoice_date: formData.value.invoice_date,
            discount_rate: formData.value.discount_rate,
            discount_amount: formData.value.discount_amount,
            tax_rate: formData.value.tax_rate,
            tax_amount: formData.value.tax_amount,
            shipping_cost: formData.value.shipping_cost,
            subtotal: formData.value.subtotal,
            total_amount: formData.value.total_amount,
            status: formData.value.is_credit ? 'unpaid' : 'fully-paid',
            is_credit: formData.value.type === 'sales-invoice' ? formData.value.is_credit : false,
            payment_method: formData.value.payment_method,
            items: formData.value.items.map(item => ({
                warehouse_product_id: item.warehouse_product_id,
                qty: item.qty,
                price: item.price,
                total: item.total,
                serials: item.serials || []
            }))
        };

        // Prepare payment details
        const paymentDetails = {
            account_number: formData.value.payment_details.account_number || null,
            account_name: formData.value.payment_details.account_name || null,
            bank_id: formData.value.payment_details.bank_id || null,
            company_account_id: formData.value.payment_details.company_account_id || null,
            reference_number: formData.value.payment_details.reference_number || null,
            status: 'approved',
            payment_date: new Date().toISOString().split('T')[0],
            amount: formData.value.total_amount
        };

        let response;
        // If there's a receipt attachment, use FormData
        if (formData.value.payment_details.receipt_attachment) {
            const formDataObj = new FormData();
            
            // Add all regular fields
            Object.entries(submitData).forEach(([key, value]) => {
                if (key !== 'items' && key !== 'payment_details') {
                    formDataObj.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                }
            });

            // Add items as array notation
            submitData.items.forEach((item, index) => {
                // Add regular item fields
                formDataObj.append(`items[${index}][warehouse_product_id]`, item.warehouse_product_id);
                formDataObj.append(`items[${index}][qty]`, item.qty);
                formDataObj.append(`items[${index}][price]`, item.price);
                formDataObj.append(`items[${index}][total]`, item.total);

                // Handle serials array
                if (item.serials && Array.isArray(item.serials)) {
                    item.serials.forEach((serial, serialIndex) => {
                        formDataObj.append(`items[${index}][serials][${serialIndex}]`, serial);
                    });
                } else {
                    // If no serials, add empty array indicator
                    formDataObj.append(`items[${index}][serials][]`, '');
                }
            });

            // Add payment details as array notation
            Object.entries(paymentDetails).forEach(([key, value]) => {
                formDataObj.append(`payment_details[${key}]`, value === null ? '' : value);
            });

            // Add the file
            formDataObj.append('receipt_attachment', formData.value.payment_details.receipt_attachment);

            response = await axios.post('/api/invoices', formDataObj, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
        } else {
            // Regular JSON submission
            response = await axios.post('/api/invoices', {
                ...submitData,
                payment_details: paymentDetails
            });
        }

        toast.success('Invoice created successfully!');
        router.get(`/invoices/${response.data.data.id}`);
    } catch (error) {
        console.error('Form Data:', error.response?.data);
        toast.error(error.response?.data?.message || 'Error creating invoice');
    } finally {
        isSubmitting.value = false;
    }
};

const saveAsDraft = async () => {
    try {
        const errors = validateForm();
        if (Object.keys(errors).length > 0) {
            Object.values(errors).forEach(error => toast.error(error));
            return;
        }

        isSubmitting.value = true;

        // Create base data object
        const submitData = {
            company_id: formData.value.company_id,
            customer_id: formData.value.customer_id,
            warehouse_id: formData.value.warehouse_id,
            type: formData.value.type,
            invoice_date: formData.value.invoice_date,
            discount_rate: formData.value.discount_rate,
            discount_amount: formData.value.discount_amount,
            tax_rate: formData.value.tax_rate,
            tax_amount: formData.value.tax_amount,
            shipping_cost: formData.value.shipping_cost,
            subtotal: formData.value.subtotal,
            total_amount: formData.value.total_amount,
            status: 'draft',
            is_credit: formData.value.type === 'sales-invoice' ? formData.value.is_credit : false,
            payment_method: formData.value.payment_method,
            items: formData.value.items.map(item => ({
                warehouse_product_id: item.warehouse_product_id,
                qty: item.qty,
                price: item.price,
                total: item.total,
                serials: item.serials || []
            }))
        };

        // Prepare payment details
        const paymentDetails = {
            account_number: formData.value.payment_details.account_number || null,
            account_name: formData.value.payment_details.account_name || null,
            bank_id: formData.value.payment_details.bank_id || null,
            company_account_id: formData.value.payment_details.company_account_id || null,
            reference_number: formData.value.payment_details.reference_number || null,
            status: 'pending',
            payment_date: null,
            amount: formData.value.total_amount
        };

        let response;
        // If there's a receipt attachment, use FormData
        if (formData.value.payment_details.receipt_attachment) {
            const formDataObj = new FormData();
            
            // Add all regular fields
            Object.entries(submitData).forEach(([key, value]) => {
                if (key !== 'items' && key !== 'payment_details') {
                    formDataObj.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                }
            });

            // Add items as array notation
            submitData.items.forEach((item, index) => {
                // Add regular item fields
                formDataObj.append(`items[${index}][warehouse_product_id]`, item.warehouse_product_id);
                formDataObj.append(`items[${index}][qty]`, item.qty);
                formDataObj.append(`items[${index}][price]`, item.price);
                formDataObj.append(`items[${index}][total]`, item.total);

                // Handle serials array
                if (item.serials && Array.isArray(item.serials)) {
                    item.serials.forEach((serial, serialIndex) => {
                        formDataObj.append(`items[${index}][serials][${serialIndex}]`, serial);
                    });
                } else {
                    // If no serials, add empty array indicator
                    formDataObj.append(`items[${index}][serials][]`, '');
                }
            });

            // Add payment details as array notation
            Object.entries(paymentDetails).forEach(([key, value]) => {
                formDataObj.append(`payment_details[${key}]`, value === null ? '' : value);
            });

            // Add the file
            formDataObj.append('receipt_attachment', formData.value.payment_details.receipt_attachment);

            response = await axios.post('/api/invoices', formDataObj, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
        } else {
            // Regular JSON submission
            response = await axios.post('/api/invoices', {
                ...submitData,
                payment_details: paymentDetails
            });
        }

        toast.success('Invoice saved as draft!');
        router.get(`/invoices/${response.data.data.id}`);
    } catch (error) {
        console.error('Form Data:', error.response?.data);
        toast.error(error.response?.data?.message || 'Error saving draft');
    } finally {
        isSubmitting.value = false;
    }
};

// Autocomplete mapping functions
const mapCompany = (company) => ({
    ...company,
    viewUrl: `/companies/${company.id}`,
});

const mapWarehouse = (warehouse) => ({
    ...warehouse,
    viewUrl: `/warehouses/${warehouse.id}`,
});

const mapCustomer = (customer) => ({
    ...customer,
    viewUrl: `/customers/${customer.id}`,
});

// Payment methods
const searchBanks = debounce(async () => {
    if (!bankSearch.value) {
        bankResults.value = [];
        return;
    }

    try {
        const response = await axios.get('/api/autocomplete/banks', {
            params: { search: bankSearch.value }
        });
        bankResults.value = response.data.data || [];
        showBankResults.value = true;
    } catch (error) {
        console.error('Error searching banks:', error);
        bankResults.value = [];
    }
}, 300);

const searchCompanyAccounts = debounce(async () => {
    if (!companyAccountSearch.value) {
        companyAccountResults.value = [];
        return;
    }

    try {
        const response = await axios.get('/api/autocomplete/company-accounts', {
            params: { search: companyAccountSearch.value }
        });
        companyAccountResults.value = response.data.data || [];
        showCompanyAccountResults.value = true;
    } catch (error) {
        console.error('Error searching company accounts:', error);
        companyAccountResults.value = [];
    }
}, 300);

const selectBank = (bank) => {
    selectedBank.value = bank;
    bankSearch.value = bank.name;
    formData.value.payment_details.bank_id = bank.id;
    showBankResults.value = false;
};

const selectCompanyAccount = (account) => {
    selectedCompanyAccount.value = account;
    companyAccountSearch.value = account.name;
    formData.value.payment_details.company_account_id = account.id;
    showCompanyAccountResults.value = false;
};

// Add or update the showSerialListModal function
const showSerialListModal = (item) => {
    selectedItem.value = item;
    showSerialListModalFlag.value = true;
};

// Update the removeSerial function
const removeSerial = (item, serial) => {
    const index = item.serials.indexOf(serial);
    if (index > -1) {
        item.serials.splice(index, 1);
        item.qty = item.serials.length;
        item.total = item.price * item.qty;

        if (item.qty === 0) {
            removeItem(item);
            showSerialListModalFlag.value = false;
        }
    }
};
</script>

<template>
    <AppLayout title="Create Invoice">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create Invoice
                </h2>
                <HeaderActions :actions="[
                    {
                        text: 'Go Back',
                        url: '/invoices',
                        inertia: true,
                        class: 'border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600'
                    }
                ]" />
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Create Invoice</h3>
                </div>

                <!-- Main Content -->
                <div class="mt-6 space-y-8">
                    <!-- Company and Customer Selection -->
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <!-- Company Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                            <Autocomplete
                                search-url="/api/autocomplete/companies"
                                placeholder="Search for a company..."
                                model-name="companies"
                                :map-custom-buttons="mapCompany"
                                @select="(data) => selectedCompany = data.data[0]"
                            />
                        </div>

                        <!-- Warehouse Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse</label>
                            <select
                                v-model="selectedWarehouse"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                                :disabled="!selectedCompany"
                            >
                                <option value="">Select Warehouse</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse">
                                    {{ warehouse.name }}
                                </option>
                            </select>
                            <p v-if="!selectedCompany" class="mt-1 text-sm text-gray-500">
                                Please select a company first
                            </p>
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                            <select
                                v-model="selectedCustomer"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                                :disabled="!selectedCompany"
                            >
                                <option value="">Select Customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer">
                                    {{ customer.name }}
                                </option>
                            </select>
                            <p v-if="!selectedCompany" class="mt-1 text-sm text-gray-500">
                                Please select a company first
                            </p>
                        </div>
                    </div>

                    <!-- Product Search and Table -->
                    <div class="space-y-4">
                        <!-- Product Search with Autocomplete -->
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @input="searchProducts"
                                @focus="showSearchResults = true"
                                placeholder="Search products..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <!-- Search Results Dropdown -->
                            <div
                                v-if="showSearchResults && searchResults.length > 0"
                                class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200"
                            >
                                <ul class="max-h-60 overflow-auto py-1">
                                    <li
                                        v-for="product in searchResults"
                                        :key="product.id"
                                        @click="selectProduct(product)"
                                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                    >
                                        <div class="font-medium">
                                            {{ product.supplier_product_detail?.product?.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            SKU: {{ product.supplier_product_detail?.product?.slug }}
                                            | Price: {{ formatNumber(product.price, { style: 'currency', currency: 'PHP' }) }}
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in formData.items" :key="item.warehouse_product_id">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div>{{ item.name }}</div>
                                            <div v-if="item.has_serials" class="mt-1">
                                                <button 
                                                    @click="showSerialListModal(item)"
                                                    class="text-xs text-blue-600 hover:text-blue-800"
                                                >
                                                    Serial Numbers:
                                                    <span v-for="serial in item.serials" 
                                                        :key="serial" 
                                                        class="mr-1"
                                                    >
                                                        {{ serial }}{{ item.serials.indexOf(serial) < item.serials.length - 1 ? ',' : '' }}
                        </span>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                            {{ formatNumber(item.price, { style: 'currency', currency: 'PHP' }) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                            <div class="flex items-center justify-end space-x-2">
                                                <template v-if="!item.has_serials">
                                                    <button 
                                                        @click="updateItemQuantity(item, -1)" 
                                                        class="text-gray-500 hover:text-gray-700"
                                                    >
                                                        <span class="text-xl">-</span>
                                                    </button>
                                                    <span class="w-8 text-center">{{ item.qty }}</span>
                                                    <button 
                                                        @click="updateItemQuantity(item, 1)" 
                                                        class="text-gray-500 hover:text-gray-700"
                                                    >
                                                        <span class="text-xl">+</span>
                    </button>
                </template>
                                                <template v-else>
                                                    <span class="w-8 text-center">{{ item.serials.length }}</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                            {{ formatNumber(item.total, { style: 'currency', currency: 'PHP' }) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="removeItem(item)" class="text-red-600 hover:text-red-900">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="formData.items.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No items added yet. Use the search box above to add products.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Details and Totals -->
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <!-- Payment Method Selection -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900">Payment Details</h4>

                            <!-- Credit Toggle - Only show for sales-invoice -->
                            <div v-if="formData.type === 'sales-invoice'" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h5 class="font-medium text-gray-900">Credit Invoice</h5>
                                    <p class="text-sm text-gray-500">Toggle this if this is a credit invoice</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="formData.is_credit" 
                                        class="sr-only peer"
                                    >
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Payment Method Selection (only show if not credit and is sales invoice) -->
                            <template v-if="!formData.is_credit && formData.type === 'sales-invoice'">
                                <div class="space-y-4">
                                    <select
                                        v-model="formData.payment_method"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Select payment method</option>
                                        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                            {{ method.name }}
                                        </option>
                                    </select>

                                    <!-- Payment Details Based on Method -->
                                    <template v-if="formData.payment_method">
                                        <!-- Common fields for all payment methods except cash -->
                                        <template v-if="formData.payment_method !== 'cash'">
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Reference Number <span class="text-red-500">*</span></label>
                                                    <input
                                                        type="text"
                                                        v-model="formData.payment_details.reference_number"
                                                        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Enter reference number"
                                                        required
                                                    />
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Receipt Attachment</label>
                                                    <Dropzone
                                                        id="receipt-attachment"
                                                        label="Upload Receipt (Optional)"
                                                        v-model="formData.payment_details.receipt_attachment"
                                                    />
                                                </div>
                                            </div>
                                        </template>

                                        <!-- ... rest of the payment method specific fields ... -->
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Totals Breakdown -->
                        <div class="bg-gray-50 p-6 rounded-lg space-y-4">
                            <h4 class="font-medium text-gray-900">Invoice Summary</h4>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">
                                    {{ formatNumber(subtotal, { style: 'currency', currency: 'PHP' }) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm items-center">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">Tax Rate</span>
                                    <input
                                        type="number"
                                        v-model.number="formData.tax_rate"
                                        class="w-16 px-2 py-1 border border-gray-300 rounded"
                                        min="0"
                                        max="100"
                                    />
                                    <span class="text-gray-600">%</span>
                                </div>
                                <span class="font-medium">
                                    {{ formatNumber(taxAmount, { style: 'currency', currency: 'PHP' }) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm items-center">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">Discount</span>
                                    <select
                                        v-model="formData.discount_type"
                                        class="px-2 py-1 border border-gray-300 rounded"
                                    >
                                        <option value="percentage">%</option>
                                        <option value="fixed">Fixed</option>
                                    </select>
                                    <input
                                        type="number"
                                        v-model.number="formData.discount_rate"
                                        class="w-16 px-2 py-1 border border-gray-300 rounded"
                                        min="0"
                                        :max="formData.discount_type === 'percentage' ? 100 : undefined"
                                    />
                                </div>
                                <span class="font-medium text-green-600">
                                    -{{ formatNumber(discountAmount, { style: 'currency', currency: 'PHP' }) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm items-center">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">Shipping</span>
                                    <input
                                        type="number"
                                        v-model.number="formData.shipping_cost"
                                        class="w-24 px-2 py-1 border border-gray-300 rounded"
                                        min="0"
                                    />
                                </div>
                                <span class="font-medium">
                                    {{ formatNumber(formData.shipping_cost, { style: 'currency', currency: 'PHP' }) }}
                                </span>
                            </div>

                            <div class="pt-4 border-t border-gray-200">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span>
                                        {{ formatNumber(totalAmount, { style: 'currency', currency: 'PHP' }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-6">
                        <Link
                            :href="route('invoices.index')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </Link>
                        <!-- <button
                            type="button"
                            @click="saveAsDraft"
                            :disabled="isSubmitting"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <span v-if="isSubmitting">Saving...</span>
                            <span v-else>Save as Draft</span>
                        </button> -->
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="isSubmitting"
                            class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700"
                        >
                            <span v-if="isSubmitting">Creating...</span>
                            <span v-else>Create Invoice</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Serial Number Modal -->
        <TransitionRoot appear :show="showSerialModal" as="template">
            <Dialog as="div" @close="closeSerialModal" class="relative z-10">
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                    Enter Serial Number
                                </DialogTitle>

                                <div class="mt-4">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <span class="text-2xl">📦</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                {{ currentProduct?.supplier_product_detail?.product?.name }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{ formatNumber(currentProduct?.price, { style: 'currency', currency: 'PHP' }) }}
                                            </p>
                                            <p class="text-xs text-blue-600">
                                                {{ formData.items.find(i => i.warehouse_product_id === currentProduct?.id)?.serials?.length || 0 }} serial/batch number(s)
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                                            <input
                                                type="text"
                                                v-model="serialNumber"
                                                @keyup.enter="handleSerialSubmit"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Enter or scan serial number"
                                                ref="serialInput"
                                                autofocus
                                            />
                                            <p v-if="serialError" class="mt-2 text-sm text-red-600">{{ serialError }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="closeSerialModal"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Done
                                    </button>
                                    <button
                                        @click="handleSerialSubmit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Add Serial
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Serial List Modal -->
        <TransitionRoot appear :show="showSerialListModalFlag" as="template">
            <Dialog as="div" @close="showSerialListModalFlag = false" class="relative z-10">
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                    Serial Numbers
                                </DialogTitle>

                                <div class="mt-4">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <span class="text-2xl">📦</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                {{ selectedItem?.name }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{ formatNumber(selectedItem?.price, { style: 'currency', currency: 'PHP' }) }}
                                            </p>
                                            <p class="text-xs text-blue-600">
                                                {{ selectedItem?.serials?.length || 0 }} serial/batch number(s)
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div v-for="serial in selectedItem?.serials" :key="serial" 
                                            class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
                                        >
                                            <span class="font-medium">{{ serial }}</span>
                                            <button 
                                                @click="removeSerial(selectedItem, serial)"
                                                class="text-red-600 hover:text-red-800"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button
                                        @click="showSerialListModalFlag = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Close
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>
