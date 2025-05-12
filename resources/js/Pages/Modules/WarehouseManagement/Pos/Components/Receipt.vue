<script setup>
import { formatNumber } from "@/utils/global";

defineEmits(['newTransaction']);

const props = defineProps({
    invoice: {
        type: Object,
        required: true
    },
    paymentDetails: {
        type: Object,
        required: true
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
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

const print = () => {
    const receiptContent = document.getElementById('receipt').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Receipt</title>');
    printWindow.document.write(`
        <style>
            body { 
                font-family: system-ui, -apple-system, sans-serif;
                padding: 2rem;
                margin: 0;
                background: #fff;
            }
            #receipt {
                max-width: 100%;
                margin: 0 auto;
                background: white;
                padding: 2rem;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .font-bold { font-weight: 700; }
            .text-2xl { font-size: 1.5rem; line-height: 2rem; }
            .text-gray-500 { color: #6b7280; }
            .text-gray-400 { color: #9ca3af; }
            .text-gray-600 { color: #4b5563; }
            .text-green-600 { color: #059669; }
            .border-t { border-top: 1px solid #e5e7eb; }
            .border-b { border-bottom: 1px solid #e5e7eb; }
            .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
            .mb-6 { margin-bottom: 1.5rem; }
            .mt-8 { margin-top: 2rem; }
            .pt-2 { padding-top: 0.5rem; }
            .space-y-2 > * + * { margin-top: 0.5rem; }
            .grid { display: grid; }
            .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .gap-4 { gap: 1rem; }
            table { width: 100%; border-collapse: collapse; }
            th { padding: 0.5rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
            td { padding: 0.5rem; border-bottom: 1px solid #f3f4f6; }
            .text-sm { font-size: 0.875rem; }
            .font-medium { font-weight: 500; }
            @media print {
                body { padding: 0; }
                #receipt { box-shadow: none; }
            }
        </style>
    `);
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div id="receipt">');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</div></body></html>');
    printWindow.document.close();
    printWindow.print();
};
</script> 

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
                    <h1 class="text-2xl font-bold">{{ invoice.company?.name || 'Company Name' }}</h1>
                    <p class="text-gray-500">{{ invoice.company?.address || '123 Business Street' }}</p>
                    <p class="text-gray-500">{{ invoice.company?.mobile || 'Tel: (123) 456-7890' }}</p>
                </div>

                <!-- Order Info -->
                <div class="border-t border-b border-gray-200 py-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Receipt No.</p>
                            <p class="font-medium">{{ invoice.number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="font-medium">{{ formatDate(invoice.invoice_date) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="font-medium">{{ invoice.customer?.name }}</p>
                            <p class="text-sm text-gray-500">{{ invoice.customer?.mobile }}</p>
                            <p v-if="invoice.customer?.address" class="text-sm text-gray-500">{{ invoice.customer.address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Payment Method</p>
                            <p class="font-medium capitalize">{{ formatPaymentMethod(invoice.payment_method_details?.payment_method) }}</p>
                            
                            <!-- Cash payment - only show method -->
                            <template v-if="invoice.payment_method_details?.payment_method === 'cash'">
                                <p class="text-sm text-gray-500 mt-1">Amount Paid</p>
                                <p class="font-medium">{{ formatNumber(invoice.payment_method_details.amount, { style: 'currency', currency: 'PHP' }) }}</p>
                            </template>

                            <!-- GCash payment -->
                            <template v-if="invoice.payment_method_details?.payment_method === 'gcash'">
                                <p class="text-sm text-gray-500 mt-1">Mobile Number</p>
                                <p class="font-medium">{{ invoice.payment_method_details.account_number }}</p>
                                <p class="text-sm text-gray-500 mt-1">Amount Paid</p>
                                <p class="font-medium">{{ formatNumber(invoice.payment_method_details.amount, { style: 'currency', currency: 'PHP' }) }}</p>
                            </template>

                            <!-- Credit Card payment -->
                            <template v-if="invoice.payment_method_details?.payment_method === 'credit-card'">
                                <p class="text-sm text-gray-500 mt-1">Account Number</p>
                                <p class="font-medium">{{ invoice.payment_method_details.account_number }}</p>
                                <p class="text-sm text-gray-500 mt-1">Account Name</p>
                                <p class="font-medium">{{ invoice.payment_method_details.account_name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Amount Paid</p>
                                <p class="font-medium">{{ formatNumber(invoice.payment_method_details.amount, { style: 'currency', currency: 'PHP' }) }}</p>
                            </template>

                            <!-- Bank Transfer payment -->
                            <template v-if="invoice.payment_method_details?.payment_method === 'bank-transfer'">
                                <p class="text-sm text-gray-500 mt-1">Bank</p>
                                <p class="font-medium">{{ invoice.payment_method_details.bank?.name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Account Number</p>
                                <p class="font-medium">{{ invoice.payment_method_details.account_number }}</p>
                                <p class="text-sm text-gray-500 mt-1">Account Name</p>
                                <p class="font-medium">{{ invoice.payment_method_details.account_name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Company Account</p>
                                <p class="font-medium">{{ invoice.payment_method_details.company_account?.name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Amount Paid</p>
                                <p class="font-medium">{{ formatNumber(invoice.payment_method_details.amount, { style: 'currency', currency: 'PHP' }) }}</p>
                            </template>

                            <!-- Reference Number if available -->
                            <template v-if="invoice.payment_method_details?.reference_number">
                                <p class="text-sm text-gray-500 mt-1">Reference Number</p>
                                <p class="font-medium">{{ invoice.payment_method_details.reference_number }}</p>
                            </template>
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
                            <tr v-for="detail in invoice.details" :key="detail.id" class="border-b border-gray-100">
                                <td class="py-2">{{ detail.warehouse_product?.supplier_product_detail?.product?.name }}</td>
                                <td class="py-2 text-right">{{ detail.qty }}</td>
                                <td class="py-2 text-right">{{ formatNumber(detail.price, { style: 'currency', currency: 'PHP' }) }}</td>
                                <td class="py-2 text-right">{{ formatNumber(detail.total, { style: 'currency', currency: 'PHP' }) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span>{{ formatNumber(invoice.subtotal, { style: 'currency', currency: 'PHP' }) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">VAT ({{ invoice.tax_rate }}%)</span>
                        <span>{{ formatNumber(invoice.tax_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Discount</span>
                        <span class="text-green-600">-{{ formatNumber(invoice.discount_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span>{{ formatNumber(invoice.shipping_cost, { style: 'currency', currency: 'PHP' }) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total</span>
                        <span>{{ formatNumber(invoice.total_amount, { style: 'currency', currency: 'PHP' }) }}</span>
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