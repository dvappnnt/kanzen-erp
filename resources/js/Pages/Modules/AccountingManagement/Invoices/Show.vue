<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import { formatNumber } from "@/utils/global";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const headerActions = ref([
    {
        text: "Go Back",
        url: "/invoices",
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
    {
        text: "Print Invoice",
        click: () => printInvoice(),
        class: "bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white",
    },
]);

const formatDate = (date) => {
    return moment(date).format('MMMM D, YYYY');
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

const printInvoice = () => {
    window.print();
};

console.log(modelData.value);
</script>

<template>
    <AppLayout title="Sales Invoice">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Sales Invoice
                </h2>
                <div class="flex gap-2 print:hidden">
                    <button
                        v-for="action in headerActions"
                        :key="action.text"
                        @click="action.click ? action.click() : null"
                        :class="action.class"
                    >
                        {{ action.text }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div id="invoice" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                    <!-- Header Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <div class="flex justify-between">
                            <!-- Company Info -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">{{ modelData.company?.name }}</h1>
                                <p class="text-gray-600">{{ modelData.company?.address }}</p>
                                <p class="text-gray-600">{{ modelData.company?.mobile }}</p>
                                <p class="text-gray-600">{{ modelData.company?.email }}</p>
                            </div>
                            <!-- Invoice Info -->
                            <div class="text-right">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">SALES INVOICE</h2>
                                <p class="text-gray-600">Invoice #: <span class="font-semibold">{{ modelData.number }}</span></p>
                                <p class="text-gray-600">Date: <span class="font-semibold">{{ formatDate(modelData.invoice_date) }}</span></p>
                                <p class="text-gray-600">Due Date: <span class="font-semibold">{{ modelData.due_date ? formatDate(modelData.due_date) : '-' }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Bill To & Ship To Section -->
                    <div class="grid grid-cols-2 gap-8 py-8 border-b border-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Bill To:</h3>
                            <p class="font-medium text-gray-800">{{ modelData.customer?.name }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.address }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.phone }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.email }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Ship From:</h3>
                            <p class="font-medium text-gray-800">{{ modelData.warehouse?.name }}</p>
                            <p class="text-gray-600">{{ modelData.warehouse?.address }}</p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="py-8 border-b border-gray-200">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left">
                                    <th class="pb-4 text-gray-600 text-sm font-semibold">Item Description</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Unit</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Qty</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Price</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="border-t border-gray-100">
                                <tr v-for="detail in modelData.details" :key="detail.id" class="border-b border-gray-50">
                                    <td class="py-4">
                                        <p class="font-medium text-gray-800">
                                            {{ detail.warehouse_product?.supplier_product_detail?.product?.name }}
                                        </p>
                                        <p v-if="detail.invoice_serials?.length" class="text-sm text-gray-500 mt-1">
                                            Serial/Batch Numbers: {{ detail.invoice_serials.map(serial => {
                                                const productSerial = serial.warehouse_product_serial;
                                                return `${productSerial.serial_number}${productSerial.batch_number ? ` (Batch: ${productSerial.batch_number})` : ''}${
                                                    productSerial.manufactured_at ? ` | Manufactured: ${formatDate(productSerial.manufactured_at)}` : ''
                                                }${
                                                    productSerial.expired_at ? ` | Expires: ${formatDate(productSerial.expired_at)}` : ''
                                                }`;
                                            }).join(', ') }}
                                        </p>
                                    </td>
                                    <td class="py-4 text-right">{{ detail.warehouse_product?.supplier_product_detail?.product?.unit_of_measure }}</td>
                                    <td class="py-4 text-right">{{ detail.qty }}</td>
                                    <td class="py-4 text-right">{{ formatNumber(detail.price, { style: 'currency', currency: modelData.currency }) }}</td>
                                    <td class="py-4 text-right">{{ formatNumber(detail.total, { style: 'currency', currency: modelData.currency }) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Section -->
                    <div class="py-8 grid grid-cols-2 gap-8">
                        <!-- Payment Information -->
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Payment Information</h3>
                            <div class="space-y-2">
                                <p class="text-gray-600">
                                    Method: <span class="font-medium">{{ formatPaymentMethod(modelData.payment_method_details?.[0]?.payment_method) }}</span>
                                </p>
                                <template v-if="modelData.payment_method_details?.[0]?.account_number">
                                    <p class="text-gray-600">
                                        Account: <span class="font-medium">{{ modelData.payment_method_details[0].account_number }}</span>
                                    </p>
                                </template>
                                <p class="text-gray-600">
                                    Status: <span class="font-medium capitalize">{{ modelData.status }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">{{ formatNumber(modelData.subtotal, { style: 'currency', currency: modelData.currency }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT ({{ modelData.tax_rate }}%):</span>
                                <span class="font-medium">{{ formatNumber(modelData.tax_amount, { style: 'currency', currency: modelData.currency }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Discount:</span>
                                <span class="font-medium text-green-600">-{{ formatNumber(modelData.discount_amount, { style: 'currency', currency: modelData.currency }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping:</span>
                                <span class="font-medium">{{ formatNumber(modelData.shipping_cost, { style: 'currency', currency: modelData.currency }) }}</span>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="font-semibold text-gray-800">Total:</span>
                                <span class="font-bold text-gray-800">{{ formatNumber(modelData.total_amount, { style: 'currency', currency: modelData.currency }) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-gray-200 text-center text-gray-500 text-sm">
                        <p>Thank you for your business!</p>
                        <p class="mt-1">For questions about this invoice, please contact {{ modelData.company?.name }}</p>
                        <p class="mt-1">{{ modelData.company?.email }} | {{ modelData.company?.mobile }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
    }
    .print\\:hidden {
        display: none !important;
    }
    #invoice {
        box-shadow: none;
        padding: 0;
    }
}
</style>
