<template>
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50 flex items-center justify-center p-6">
        <div class="max-w-lg w-full">
            <!-- Receipt Actions -->
            <div class="flex justify-between mb-6">
                <button 
                    @click="$emit('newTransaction')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                    New Transaction
                </button>
                <button 
                    @click="print"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium"
                >
                    Print Receipt
                </button>
            </div>

            <!-- Receipt Content -->
            <div id="receipt" class="bg-white rounded-xl shadow-sm p-8">
                <!-- Header -->
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold">Company Name</h1>
                    <p class="text-gray-500">123 Business Street</p>
                    <p class="text-gray-500">City, Country</p>
                    <p class="text-gray-500">Tel: (123) 456-7890</p>
                </div>

                <!-- Order Info -->
                <div class="border-t border-b border-gray-200 py-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Receipt No.</p>
                            <p class="font-medium">#INV-{{ new Date().getTime().toString().slice(-6) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="font-medium">{{ new Date().toLocaleDateString() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="font-medium">{{ customerInfo.name }}</p>
                            <p class="text-sm text-gray-500">{{ customerInfo.mobile }}</p>
                            <p v-if="customerInfo.address" class="text-sm text-gray-500">{{ customerInfo.address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Payment Method</p>
                            <p class="font-medium">Cash</p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-2 text-left">Item</th>
                                <th class="py-2 text-right">Qty</th>
                                <th class="py-2 text-right">Price</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="i in 3" :key="i" class="border-b border-gray-100">
                                <td class="py-2">Product {{ i }}</td>
                                <td class="py-2 text-right">2</td>
                                <td class="py-2 text-right">₱{{ (Math.random() * 100).toFixed(2) }}</td>
                                <td class="py-2 text-right">₱{{ (Math.random() * 200).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span>₱2,450.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">VAT (12%)</span>
                        <span>₱294.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Discount</span>
                        <span class="text-green-600">-₱100.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span>₱100.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total</span>
                        <span>₱2,744.00</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-gray-500">Thank you for your business!</p>
                    <p class="text-sm text-gray-400">Keep this receipt for your records</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineEmits(['newTransaction']);
defineProps({
    customerInfo: {
        type: Object,
        required: true
    }
});

const print = () => {
    const receiptContent = document.getElementById('receipt').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>body { font-family: system-ui, sans-serif; padding: 2rem; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
};
</script> 