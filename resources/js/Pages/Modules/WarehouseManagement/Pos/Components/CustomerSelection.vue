<template>
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50 flex items-center justify-center">
        <div class="max-w-2xl w-full mx-auto p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Start New Transaction</h2>
            
            <div class="space-y-6">
                <!-- Customer Type Selection -->
                <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="text-lg font-semibold">Select Customer Type</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            @click="handleCustomerButtonClick('new')"
                            class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all"
                        >
                            <span class="text-4xl mb-2">👤</span>
                            <span class="font-medium">New Customer</span>
                            <span class="text-sm text-gray-500">Quick transaction</span>
                        </button>
                        
                        <button 
                            @click="handleCustomerButtonClick('existing')"
                            class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all"
                        >
                            <span class="text-4xl mb-2">👥</span>
                            <span class="font-medium">Existing Customer</span>
                            <span class="text-sm text-gray-500">Select from database</span>
                        </button>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
                    <div class="space-y-3">
                        <div v-for="i in 3" :key="i" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">Transaction #{{ 1000 + i }}</p>
                                <p class="text-sm text-gray-500">John Doe - {{ new Date().toLocaleDateString() }}</p>
                            </div>
                            <span class="text-blue-600 font-semibold">₱{{ (Math.random() * 1000).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Customer Modal -->
        <TransitionRoot appear :show="showNewCustomerModal" as="template">
            <Dialog as="div" @close="showNewCustomerModal = false" class="relative z-10">
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
                            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-8 shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-xl font-medium leading-6 text-gray-900 mb-6">
                                    New Customer Information
                                </DialogTitle>

                                <form @submit.prevent="handleNewCustomer" class="space-y-6">
                                    <!-- Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Name *
                                        </label>
                                        <input
                                            type="text"
                                            v-model="customerForm.name"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Enter customer name"
                                        />
                                    </div>

                                    <div class="grid grid-cols-2 gap-6">
                                        <!-- Mobile -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Mobile *
                                            </label>
                                            <input
                                                type="tel"
                                                v-model="customerForm.mobile"
                                                required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter mobile number"
                                            />
                                        </div>

                                        <!-- Landline -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Landline
                                            </label>
                                            <input
                                                type="tel"
                                                v-model="customerForm.landline"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter landline number (optional)"
                                            />
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Address
                                        </label>
                                        <textarea
                                            v-model="customerForm.address"
                                            rows="4"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Enter address (optional)"
                                        ></textarea>
                                    </div>

                                    <div class="mt-8 flex justify-end gap-4">
                                        <button
                                            type="button"
                                            @click="showNewCustomerModal = false"
                                            class="px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            class="px-6 py-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"
                                        >
                                            Proceed
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Existing Customer Modal -->
        <TransitionRoot appear :show="showExistingCustomerModal" as="template">
            <Dialog as="div" @close="showExistingCustomerModal = false" class="relative z-10">
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
                            <DialogPanel class="w-full max-w-4xl transform overflow-visible rounded-2xl bg-white p-8 shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-2xl font-medium leading-6 text-gray-900 mb-8">
                                    Select Existing Customer
                                </DialogTitle>

                                <div class="space-y-8">
                                    <!-- Search Input -->
                                    <div>
                                        <label class="block text-base font-medium text-gray-700 mb-2">
                                            Search Customer
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input
                                                type="text"
                                                v-model="searchQuery"
                                                @input="searchCustomers"
                                                @focus="showResults = true"
                                                class="w-full pl-10 pr-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Search by name, email, or mobile number..."
                                            />
                                            
                                            <!-- Dropdown Results -->
                                            <div v-if="showResults" 
                                                class="fixed left-1/2 transform -translate-x-1/2 top-4 w-[calc(100vw-32px)] max-w-4xl bg-white border border-gray-200 rounded-lg shadow-lg max-h-[calc(100vh-16rem)] overflow-y-auto z-50">
                                                <div class="sticky top-0 bg-gray-50 border-b border-gray-200 p-4">
                                                    <h4 class="font-medium text-gray-900">Search Results</h4>
                                                </div>
                                                <div v-if="filteredCustomers.length > 0">
                                                    <div v-for="customer in filteredCustomers" 
                                                        :key="customer.id"
                                                        @click="selectCustomer(customer)"
                                                        class="p-4 hover:bg-gray-50 cursor-pointer border-b last:border-b-0">
                                                        <div class="flex items-center space-x-4">
                                                            <div class="flex-shrink-0">
                                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                                    <span class="text-lg font-medium text-blue-600">
                                                                        {{ getInitials(customer.name) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-center justify-between">
                                                                    <p class="text-lg font-medium text-gray-900 truncate">
                                                                        {{ customer.name }}
                                                                    </p>
                                                                    <p class="text-sm text-gray-500">
                                                                        ID: {{ customer.id }}
                                                                    </p>
                                                                </div>
                                                                <div class="mt-1 flex items-center space-x-4">
                                                                    <p v-if="customer.email" class="text-sm text-gray-500 truncate">
                                                                        {{ customer.email }}
                                                                    </p>
                                                                    <p v-if="customer.mobile" class="text-sm text-gray-500">
                                                                        {{ customer.mobile }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="p-6 text-center text-gray-500">
                                                    No customers found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Customer Preview -->
                                    <div v-if="selectedCustomer" class="bg-gray-50 rounded-lg p-8">
                                        <h4 class="text-lg font-medium mb-6">Selected Customer Details</h4>
                                        <div class="grid grid-cols-2 gap-8">
                                            <div>
                                                <p class="text-sm font-medium text-gray-500 mb-1">Name</p>
                                                <p class="text-lg font-medium text-gray-900">{{ selectedCustomer.name }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500 mb-1">Email</p>
                                                <p class="text-lg font-medium text-gray-900">{{ selectedCustomer.email || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500 mb-1">Mobile</p>
                                                <p class="text-lg font-medium text-gray-900">{{ selectedCustomer.mobile || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500 mb-1">Landline</p>
                                                <p class="text-lg font-medium text-gray-900">{{ selectedCustomer.landline || 'N/A' }}</p>
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-sm font-medium text-gray-500 mb-1">Address</p>
                                                <p class="text-lg font-medium text-gray-900">{{ selectedCustomer.address || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8 flex justify-end gap-4">
                                        <button
                                            type="button"
                                            @click="showExistingCustomerModal = false"
                                            class="px-6 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-lg"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="button"
                                            @click="handleExistingCustomer"
                                            :disabled="!selectedCustomer"
                                            class="px-6 py-3 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Proceed with Selected Customer
                                        </button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';

const emit = defineEmits(['proceed']);

// New Customer Modal
const showNewCustomerModal = ref(false);
const customerForm = reactive({
    name: '',
    mobile: '',
    landline: '',
    address: ''
});

const handleNewCustomer = () => {
    emit('proceed', { type: 'new', customer: { ...customerForm } });
    showNewCustomerModal.value = false;
};

// Existing Customer Modal
const showExistingCustomerModal = ref(false);
const searchQuery = ref('');
const showResults = ref(false);
const selectedCustomer = ref(null);

// Mock data based on User model structure
const mockCustomers = [
    { 
        id: 1, 
        name: 'John Doe', 
        email: 'john@example.com',
        mobile: '+1234567890', 
        landline: '+111222333',
        address: '123 Main St, City Name, Country',
        profile_photo_url: null
    },
    { 
        id: 2, 
        name: 'Jane Smith', 
        email: 'jane@example.com',
        mobile: '+0987654321', 
        landline: '+444555666',
        address: '456 Oak Avenue, Another City, Country',
        profile_photo_url: null
    },
    { 
        id: 3, 
        name: 'Bob Johnson', 
        email: 'bob@example.com',
        mobile: '+5556667777', 
        address: '789 Pine Road, Different City',
        profile_photo_url: null
    },
    { 
        id: 4, 
        name: 'Alice Williams', 
        email: 'alice@example.com',
        mobile: '+9998887777', 
        landline: '+777888999',
        profile_photo_url: null
    },
    { 
        id: 5, 
        name: 'Charlie Brown', 
        email: 'charlie@example.com',
        mobile: '+1112223333', 
        address: '321 Elm Street, Last City',
        profile_photo_url: null
    }
];

const filteredCustomers = ref([]);

// Get initials for avatar
const getInitials = (name) => {
    if (!name) return 'N/A';
    return name
        .split(' ')
        .map((n) => n[0]?.toUpperCase())
        .slice(0, 2)
        .join('');
};

const searchCustomers = () => {
    if (!searchQuery.value) {
        filteredCustomers.value = mockCustomers;
        return;
    }
    
    const query = searchQuery.value.toLowerCase();
    filteredCustomers.value = mockCustomers.filter(customer => 
        customer.name.toLowerCase().includes(query) || 
        customer.email.toLowerCase().includes(query) ||
        (customer.mobile && customer.mobile.includes(query)) ||
        (customer.landline && customer.landline.includes(query)) ||
        (customer.address && customer.address.toLowerCase().includes(query))
    );
};

const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    showResults.value = false;
    searchQuery.value = customer.name;
};

// Close dropdown when clicking outside
const closeDropdown = (event) => {
    const isClickInsideSearchArea = event.target.closest('.relative');
    if (!isClickInsideSearchArea) {
        showResults.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

const handleExistingCustomer = () => {
    if (selectedCustomer.value) {
        emit('proceed', { type: 'existing', customer: selectedCustomer.value });
        showExistingCustomerModal.value = false;
    }
};

// Update the existing customer button click handler
const handleCustomerButtonClick = (type) => {
    if (type === 'new') {
        showNewCustomerModal.value = true;
    } else {
        showExistingCustomerModal.value = true;
        searchCustomers(); // Show all customers initially
    }
};
</script>

<style scoped>
/* Custom scrollbar for the customer list */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style> 