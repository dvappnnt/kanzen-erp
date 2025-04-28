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
                            <div v-for="i in 3" :key="i" class="flex items-center justify-between py-3 border-b last:border-0">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <span class="text-2xl">📦</span>
                                    </div>
                                    <div>
                                        <h4 class="font-medium">Product {{ i }}</h4>
                                        <p class="text-sm text-gray-500">SKU: PRD-{{ i.toString().padStart(4, '0') }}</p>
                                        <p class="text-sm text-gray-500">Qty: 2</p>
                                    </div>
                                </div>
                                <span class="font-medium">₱{{ (Math.random() * 100).toFixed(2) }}</span>
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
                                <p class="text-sm text-gray-500">Mobile</p>
                                <p class="font-medium">{{ customerInfo.mobile }}</p>
                            </div>
                            <div v-if="customerInfo.landline">
                                <p class="text-sm text-gray-500">Landline</p>
                                <p class="font-medium">{{ customerInfo.landline }}</p>
                            </div>
                            <div v-if="customerInfo.address">
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium">{{ customerInfo.address }}</p>
                            </div>
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
                                <span class="font-medium">₱2,450.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT (12%)</span>
                                <span class="font-medium">₱294.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-medium text-green-600">-₱100.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">₱100.00</span>
                            </div>
                            <div class="pt-3 border-t">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span>₱2,744.00</span>
                                </div>
                            </div>
                        </div>

                        <button 
                            @click="$emit('proceed')"
                            class="w-full mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center justify-center gap-2"
                        >
                            Confirm Order
                            <span>→</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Details</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method</span>
                                <span class="font-medium">Cash</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Status</span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">Pending</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineEmits(['back', 'proceed']);
defineProps({
    customerInfo: {
        type: Object,
        required: true
    }
});
</script> 