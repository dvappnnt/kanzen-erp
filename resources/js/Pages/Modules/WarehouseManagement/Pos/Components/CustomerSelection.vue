<template>
    <div class="max-w-3xl mx-auto py-8">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Customer Selection</h2>

            <!-- Warehouse Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Warehouse <span class="text-red-500">*</span></label>
                <select
                    v-model="selectedWarehouse"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Select a warehouse</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                        {{ warehouse.name }}
                    </option>
                </select>
            </div>

            <!-- Customer Type Selection -->
            <div class="mb-6">
                <div class="flex space-x-4">
                    <button 
                        @click="customerType = 'existing'"
                        :class="[
                            'px-4 py-2 rounded-lg flex-1 text-center',
                            customerType === 'existing' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Existing Customer
                    </button>
                    <button 
                        @click="customerType = 'new'"
                        :class="[
                            'px-4 py-2 rounded-lg flex-1 text-center',
                            customerType === 'new' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        New Customer
                    </button>
                </div>
            </div>

            <!-- Existing Customer Search -->
            <div v-if="customerType === 'existing'" class="space-y-4">
                <div class="relative">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="handleSearch"
                        placeholder="Search customer by name, email, or phone..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                    
                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Search Results -->
                    <div v-if="showResults && searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200">
                        <ul class="max-h-60 overflow-auto py-1">
                            <li 
                                v-for="customer in searchResults" 
                                :key="customer.id"
                                @click="selectCustomer(customer)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                            >
                                <div class="font-medium text-gray-900">{{ customer.name }}</div>
                                <div class="text-sm text-gray-500">{{ customer.email }}</div>
                            </li>
                        </ul>
                    </div>

                    <!-- No Results Message -->
                    <div v-if="showResults && searchResults.length === 0 && searchQuery" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 p-4 text-center text-gray-500">
                        No customers found
                    </div>
                </div>

                <!-- Selected Customer Info -->
                <div v-if="selectedCustomer" class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-2">Selected Customer</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Name:</span>
                            <span class="ml-2 text-gray-900">{{ selectedCustomer.name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Email:</span>
                            <span class="ml-2 text-gray-900">{{ selectedCustomer.email || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Phone:</span>
                            <span class="ml-2 text-gray-900">{{ selectedCustomer.phone || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Address:</span>
                            <span class="ml-2 text-gray-900">{{ selectedCustomer.address || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Customer Form -->
            <div v-else class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        v-model="newCustomer.name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-gray-500">(Optional)</span></label>
                    <input
                        type="email"
                        v-model="newCustomer.email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-500">(Optional)</span></label>
                    <input
                        type="tel"
                        v-model="newCustomer.phone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-gray-500">(Optional)</span></label>
                    <textarea
                        v-model="newCustomer.address"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <button 
                    @click="handleProceed"
                    :disabled="!isValid"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium',
                        isValid 
                            ? 'bg-blue-600 text-white hover:bg-blue-700' 
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    ]"
                >
                    Proceed to Cart
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';

const emit = defineEmits(['proceed']);

const customerType = ref('existing');
const searchQuery = ref('');
const searchResults = ref([]);
const selectedCustomer = ref(null);
const showResults = ref(false);
const isLoading = ref(false);
const selectedWarehouse = ref('');
const warehouses = ref([]);

const newCustomer = ref({
    name: '',
    email: '',
    phone: '',
    address: ''
});

// Validation
const isValid = computed(() => {
    if (!selectedWarehouse.value) return false;
    
    if (customerType.value === 'existing') {
        return selectedCustomer.value !== null;
    } else {
        return newCustomer.value.name.trim() !== '';
    }
});

// Fetch warehouses
const fetchWarehouses = async () => {
    try {
        const response = await axios.get('/api/complete/warehouses');
        warehouses.value = response.data || [];
    } catch (error) {
        console.error('Error fetching warehouses:', error);
    }
};

onMounted(() => {
    fetchWarehouses();
});

// Debounced search function
const handleSearch = debounce(async () => {
    if (!searchQuery.value) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }

    if (!selectedWarehouse.value) {
        return;
    }

    isLoading.value = true;
    showResults.value = true;

    try {
        const response = await axios.get('/api/autocomplete/customers', {
            params: { 
                search: searchQuery.value,
                warehouse_id: selectedWarehouse.value
            }
        });
        searchResults.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching customers:', error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
}, 300);

// Select customer from search results
const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    searchQuery.value = customer.name;
    showResults.value = false;
};

// Reset when switching between customer types
watch(customerType, () => {
    selectedCustomer.value = null;
    searchQuery.value = '';
    searchResults.value = [];
    showResults.value = false;
    newCustomer.value = {
        name: '',
        email: '',
        phone: '',
        address: ''
    };
});

// Reset customer search when warehouse changes
watch(selectedWarehouse, () => {
    selectedCustomer.value = null;
    searchQuery.value = '';
    searchResults.value = [];
    showResults.value = false;
});

// Proceed with customer
const handleProceed = async () => {
    try {
        if (customerType.value === 'new') {
            const warehouse = warehouses.value.find(w => w.id === selectedWarehouse.value);
            const response = await axios.post('/api/customers', {
                ...newCustomer.value,
                company_id: warehouse.company_id,
                mobile: newCustomer.value.phone // Map phone to mobile for API
            });
            
            // Emit proceed event with the newly created customer
            emit('proceed', {
                type: customerType.value,
                customer: response.data.modelData,
                warehouse_id: selectedWarehouse.value
            });
        } else {
            // Emit proceed event with existing customer
            emit('proceed', {
                type: customerType.value,
                customer: selectedCustomer.value,
                warehouse_id: selectedWarehouse.value
            });
        }
    } catch (error) {
        console.error('Error:', error);
        // Handle error appropriately - you might want to show an error message to the user
    }
};
</script>
