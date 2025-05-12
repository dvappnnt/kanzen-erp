<template>
    <AppLayout title="Point of Sale">
        <!-- Step 1: Customer Selection -->
        <CustomerSelection v-if="currentStep === 1" @proceed="handleCustomerSelection" />

        <!-- Step 2: Product Selection and Cart -->
        <div v-else-if="currentStep === 2" class="flex h-[calc(100vh-4rem)] bg-gray-50">
            <!-- Left Side - Products Section -->
            <div class="w-2/3 p-6 overflow-hidden flex flex-col">
                <!-- Search and Categories -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Search products..."
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div class="flex-1">
                        <select v-model="selectedCategory" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <option>Category 1</option>
                            <option>Category 2</option>
                            <option>Category 3</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="i in 12" :key="i" class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer p-4">
                            <div class="aspect-square bg-gray-100 rounded-lg mb-3 flex items-center justify-center">
                                <span class="text-4xl text-gray-400">📦</span>
                            </div>
                            <h3 class="font-medium text-gray-900">Product {{ i }}</h3>
                            <p class="text-gray-500 text-sm">SKU: PRD-{{ i.toString().padStart(4, '0') }}</p>
                            <p class="text-blue-600 font-semibold mt-2">₱{{ (Math.random() * 1000).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Cart Section -->
            <div class="w-1/3 bg-white border-l border-gray-200 flex flex-col">
                <!-- Cart Header -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Current Cart</h2>
                    <p class="text-sm text-gray-500">Transaction #TRX-{{ new Date().getTime().toString().slice(-6) }}</p>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-4">
                        <div v-for="i in 3" :key="i" class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-xl">📦</span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Cart Item {{ i }}</h4>
                                    <p class="text-sm text-gray-500">₱{{ (Math.random() * 100).toFixed(2) }} x 2</p>
                                </div>
                            </div>
                            <button class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-gray-200 p-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">₱2,450.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tax (12%)</span>
                        <span class="font-medium">₱294.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total</span>
                        <span>₱2,744.00</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <button 
                            @click="clearCart"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium"
                        >
                            Clear Cart
                        </button>
                        <button 
                            @click="showCheckoutModal = true"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Review Order -->
        <CheckoutReview 
            v-else-if="currentStep === 3" 
            :customer-info="customerInfo"
            @back="currentStep = 2"
            @proceed="currentStep = 4"
        />

        <!-- Step 4: Receipt -->
        <Receipt 
            v-else-if="currentStep === 4"
            :customer-info="customerInfo"
            @newTransaction="startNewTransaction"
        />

        <!-- Checkout Modal -->
        <TransitionRoot appear :show="showCheckoutModal" as="template">
            <Dialog as="div" @close="showCheckoutModal = false" class="relative z-10">
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
                                    Checkout Details
                                </DialogTitle>

                                <div class="space-y-4">
                                    <!-- Shipping -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Shipping
                                        </label>
                                        <input
                                            type="number"
                                            v-model="shipping"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <!-- Discount Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Discount Type
                                        </label>
                                        <select
                                            v-model="discountType"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="none">None</option>
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed Amount</option>
                                        </select>
                                    </div>

                                    <!-- Discount Value -->
                                    <div v-if="discountType !== 'none'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Discount Value
                                        </label>
                                        <input
                                            type="number"
                                            v-model="discountValue"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Payment Method
                                        </label>
                                        <select
                                            v-model="paymentMethod"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showCheckoutModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="proceedToReview"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Proceed to Review
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

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import CustomerSelection from "./Components/CustomerSelection.vue";
import CheckoutReview from "./Components/CheckoutReview.vue";
import Receipt from "./Components/Receipt.vue";
import { ref } from "vue";
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';

// Step Management
const currentStep = ref(1);

// Customer State
const customerInfo = ref(null);

// Product Search and Filter
const searchQuery = ref("");
const selectedCategory = ref("");

// Checkout Modal
const showCheckoutModal = ref(false);
const shipping = ref(0);
const discountType = ref("none");
const discountValue = ref(0);
const paymentMethod = ref("cash");

// Methods
const handleCustomerSelection = (data) => {
    if (data.type === 'new') {
        customerInfo.value = data.customer;
    }
    currentStep.value = 2;
};

const clearCart = () => {
    // Implementation for clearing cart
    console.log('Clearing cart...');
};

const proceedToReview = () => {
    showCheckoutModal.value = false;
    currentStep.value = 3;
};

const startNewTransaction = () => {
    customerInfo.value = null;
    currentStep.value = 1;
};
</script>

<style scoped>
/* Custom scrollbar for Webkit browsers */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
