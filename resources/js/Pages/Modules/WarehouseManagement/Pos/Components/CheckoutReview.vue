<template>
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50 flex flex-col">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h2 class="text-2xl font-bold">Review Order</h2>
                <button 
                    @click="$emit('back')"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 flex items-center gap-2"
                >
                    <span>←</span>
                    Back to Cart
                </button>
            </div>
        </div>

        <div class="flex-1 p-6">
            <div class="max-w-7xl mx-auto grid grid-cols-3 gap-6">
                <!-- Order Details -->
                <div class="col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Order Items</h3>
                        <div class="space-y-4">
                            <div v-for="item in cartItems" :key="item.id" class="flex items-center justify-between py-3 border-b last:border-0">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <img
                                            v-if="item.avatar"
                                            :src="`/storage/${item.avatar}`"
                                            :alt="item.name"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                        <span v-else class="text-4xl text-gray-400">📦</span>
                                    </div>
                                    <div>
                                        <h4 class="font-medium">{{ item.name }}</h4>
                                        <p class="text-sm text-gray-500">Qty: {{ item.quantity }}</p>
                                        <p class="text-sm text-gray-500">Price: {{ formatNumber(item.price, { style: 'currency', currency: 'PHP' }) }}</p>
                                        <p v-if="item.serials && item.serials.length > 0" 
                                           @click="showSerialListModal(item)"
                                           class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">
                                            {{ item.serials.length }} serial/batch number(s)
                                        </p>
                                    </div>
                                </div>
                                <span class="font-medium">{{ formatNumber(item.price * item.quantity, { style: 'currency', currency: 'PHP' }) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Name</p>
                                <p class="font-medium">{{ customerInfo.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium">{{ customerInfo.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="font-medium">{{ customerInfo.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium">{{ customerInfo.address || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Information</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Payment Method</p>
                                <p class="font-medium capitalize">{{ formatPaymentMethod(paymentDetails.payment_method) }}</p>
                            </div>

                            <!-- GCash Details -->
                            <template v-if="paymentDetails.payment_method === 'gcash'">
                                <div>
                                    <p class="text-sm text-gray-500">Mobile Number</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.account_number }}</p>
                                </div>
                            </template>

                            <!-- Credit Card Details -->
                            <template v-if="paymentDetails.payment_method === 'credit-card'">
                                <div>
                                    <p class="text-sm text-gray-500">Account Number</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.account_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Account Name</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.account_name }}</p>
                                </div>
                            </template>

                            <!-- Bank Transfer Details -->
                            <template v-if="paymentDetails.payment_method === 'bank-transfer'">
                                <div>
                                    <p class="text-sm text-gray-500">Account Number</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.account_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Account Name</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.account_name }}</p>
                                </div>
                                <div v-if="paymentDetails.payment_details?.bank_id">
                                    <p class="text-sm text-gray-500">Bank</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.bank?.name }}</p>
                                </div>
                                <div v-if="paymentDetails.payment_details?.company_account_id">
                                    <p class="text-sm text-gray-500">Company Account</p>
                                    <p class="font-medium">{{ paymentDetails.payment_details?.company_account?.name }}</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">{{ formatNumber(subtotal, { style: 'currency', currency: 'PHP' }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT ({{ taxRate }}%)</span>
                                <span class="font-medium">{{ formatNumber(taxAmount, { style: 'currency', currency: 'PHP' }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-medium text-green-600">-{{ formatNumber(discountAmount, { style: 'currency', currency: 'PHP' }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">{{ formatNumber(shippingAmount, { style: 'currency', currency: 'PHP' }) }}</span>
                            </div>
                            <div class="pt-3 border-t">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span>{{ formatNumber(total, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                            </div>
                        </div>

                        <button 
                            @click="showConfirmModal = true"
                            class="w-full mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center justify-center gap-2"
                        >
                            Confirm Order
                            <span>→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <DialogPanel class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all">
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Serial/Batch Numbers for {{ selectedItem?.name }}
                            </DialogTitle>

                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div v-for="serial in selectedItem?.serials" :key="serial" class="flex items-center py-3 px-4 bg-gray-50 rounded">
                                    <span class="font-medium">{{ serial }}</span>
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

    <!-- Confirmation Modal -->
    <TransitionRoot appear :show="showConfirmModal" as="template">
        <Dialog as="div" @close="showConfirmModal = false" class="relative z-10">
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
                                Confirm Order
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to process this order? This action cannot be undone.
                                </p>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    @click="showConfirmModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    :disabled="isProcessing"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="handleConfirm"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md flex items-center"
                                    :disabled="isProcessing"
                                >
                                    <span v-if="isProcessing" class="mr-2">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                    {{ isProcessing ? 'Processing...' : 'Confirm Order' }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { formatNumber } from "@/utils/global";
import { ref } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import axios from 'axios';

const emit = defineEmits(['back', 'proceed']);

const props = defineProps({
    customerInfo: {
        type: Object,
        required: true
    },
    cartItems: {
        type: Array,
        required: true
    },
    paymentDetails: {
        type: Object,
        required: true
    },
    subtotal: {
        type: Number,
        required: true
    },
    taxRate: {
        type: Number,
        required: true
    },
    taxAmount: {
        type: Number,
        required: true
    },
    discountAmount: {
        type: Number,
        required: true
    },
    discountType: {
        type: String,
        required: true
    },
    discountValue: {
        type: Number,
        required: true
    },
    shippingAmount: {
        type: Number,
        required: true
    },
    total: {
        type: Number,
        required: true
    }
});

// Serial list modal state
const showSerialListModalFlag = ref(false);
const selectedItem = ref(null);

const showSerialListModal = (item) => {
    selectedItem.value = item;
    showSerialListModalFlag.value = true;
};

const formatPaymentMethod = (method) => {
    const methods = {
        'cash': 'Cash',
        'gcash': 'GCash',
        'credit-card': 'Credit Card',
        'bank-transfer': 'Bank Transfer'
    };
    return methods[method] || method;
};

// Confirmation modal state
const showConfirmModal = ref(false);
const isProcessing = ref(false);

const handleConfirm = async () => {
    try {
        isProcessing.value = true;

        // Check if we have FormData from parent (has file upload)
        if (props.paymentDetails.formData instanceof FormData) {
            // Create a new FormData instance
            const formData = new FormData();
            
            // Add all the invoice data
            const invoiceData = props.paymentDetails.invoiceData;
            Object.entries(invoiceData).forEach(([key, value]) => {
                if (key === 'is_credit') {
                    formData.append(key, '0'); // Always false for POS
                } else if (typeof value === 'object') {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value);
                }
            });

            // Add the file
            if (props.paymentDetails.originalFile) {
                formData.append('receipt_attachment', props.paymentDetails.originalFile);
            }

            const response = await axios.post('/api/invoices', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            showConfirmModal.value = false;
            emit('proceed', response.data.data);
            return;
        }
        
        // Regular JSON submission
        const response = await axios.post('/api/invoices', {
            ...props.paymentDetails,
            is_credit: false // Always false for POS
        });

        // Close modal first
        showConfirmModal.value = false;
        
        // Then emit the proceed event with the invoice data
        emit('proceed', response.data.data);
    } catch (error) {
        console.error('Error creating invoice:', error);
        const errorMessage = error.response?.data?.message || 'Error creating invoice';
        alert(errorMessage);
    } finally {
        isProcessing.value = false;
    }
};
</script> 