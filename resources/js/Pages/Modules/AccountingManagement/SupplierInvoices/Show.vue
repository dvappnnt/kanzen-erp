<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import { formatNumber } from "@/utils/global";
import { Link } from "@inertiajs/vue3";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const headerActions = ref([
    {
        text: "Go Back",
        url: "/supplier-invoices",
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
    return date ? moment(date).format('MMMM D, YYYY') : '-';
};

const printInvoice = () => {
    window.print();
};

console.log(modelData.value);
</script>

<template>
    <AppLayout title="Supplier Invoice">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Supplier Invoice
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
                            <!-- Supplier Info -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">{{ modelData.supplier?.name }}</h1>
                                <p class="text-gray-600">{{ modelData.supplier?.address }}</p>
                                <p class="text-gray-600">{{ modelData.supplier?.phone }}</p>
                                <p class="text-gray-600">{{ modelData.supplier?.email }}</p>
                            </div>
                            <!-- Invoice Info -->
                            <div class="text-right">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">SUPPLIER INVOICE</h2>
                                <p class="text-gray-600">Invoice #: <span class="font-semibold">{{ modelData.invoice_number }}</span></p>
                                <p class="text-gray-600">Date: <span class="font-semibold">{{ formatDate(modelData.invoice_date) }}</span></p>
                                <p class="text-gray-600">Due Date: <span class="font-semibold">{{ formatDate(modelData.due_date) }}</span></p>
                                <p class="text-gray-600">Status: <span class="font-semibold capitalize">{{ modelData.status }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Company & References Section -->
                    <div class="py-6 border-b border-gray-200">
                        <div class="grid grid-cols-2 gap-8">
                            <!-- Company Info -->
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Billed To</h3>
                                <p class="text-gray-600">Company: <span class="font-semibold">{{ modelData.company?.name }}</span></p>
                                <p class="text-gray-600">Account: <span class="font-semibold">{{ modelData.company_account?.name || 'Default Account' }}</span></p>
                            </div>
                            <!-- References -->
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">References</h3>
                                <p class="text-gray-600">
                                    Purchase Order: 
                                    <Link 
                                        :href="`/purchase-orders/${modelData.purchase_order_id}`"
                                        class="font-semibold text-blue-600 hover:text-blue-800"
                                    >
                                        {{ modelData.purchase_order?.number }}
                                    </Link>
                                </p>
                                <p class="text-gray-600">
                                    Goods Receipt: 
                                    <Link 
                                        :href="`/goods-receipts/${modelData.goods_receipt_id}`"
                                        class="font-semibold text-blue-600 hover:text-blue-800"
                                    >
                                        {{ modelData.goods_receipt?.number }}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="py-8 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Details</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variation</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Quantity</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Unit Price</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="detail in modelData.details" :key="detail.id">
                                        <td class="px-2 py-2">
                                            <div class="font-medium text-gray-900">
                                                {{ detail.supplier_product?.product?.name }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                {{ detail.supplier_product?.product?.sku }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                Default
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.quantity) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.unit_price, { style: 'currency', currency: 'PHP' }) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.total, { style: 'currency', currency: 'PHP' }) }}</td>
                                    </tr>
                                    <tr v-if="!modelData.details?.length">
                                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">
                                            No items found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="py-8">
                        <div class="flex justify-end">
                            <div class="w-80 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium">{{ formatNumber(modelData.subtotal, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax ({{ modelData.tax_rate }}%):</span>
                                    <span class="font-medium">{{ formatNumber(modelData.tax_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping Cost:</span>
                                    <span class="font-medium">{{ formatNumber(modelData.shipping_cost, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200">
                                    <span class="font-semibold text-gray-800">Total Amount:</span>
                                    <span class="font-bold text-gray-800">{{ formatNumber(modelData.total_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div v-if="modelData.remarks" class="pt-8 border-t border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-2">Remarks</h3>
                        <p class="text-gray-600">{{ modelData.remarks }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-gray-200 text-center text-gray-500 text-sm">
                        <p>This is a computer-generated document. No signature is required.</p>
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
