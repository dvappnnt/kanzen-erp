<script setup>
import { onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatNumber, formatDate } from "@/utils/global";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const approvedPayments = computed(() => (modelData.value.payments || []).filter(p => p.status === 'approved'));
const totalPaid = computed(() => {
    const sum = approvedPayments.value.reduce((sum, p) => sum + parseFloat(p.amount), 0);
    return isNaN(sum) ? 0 : sum;
});
const remainingBalance = computed(() => {
    const total = parseFloat(modelData.value.total_amount) - totalPaid.value;
    return isNaN(total) ? 0 : total;
});

const basicInfo = computed(() => ({
    "Invoice Number": modelData.value.invoice_number,
    Company: modelData.value.company?.name || "—",
    Supplier: modelData.value.supplier?.name || "—",
    Status: modelData.value.status,
    "Invoice Date": modelData.value.invoice_date
        ? formatDate("M d Y", modelData.value.invoice_date)
        : "—",
    "Due Date": modelData.value.due_date
        ? formatDate("M d Y", modelData.value.due_date)
        : "—",
    "Purchase Order": modelData.value.purchase_order?.number || "—",
    "Goods Receipt": modelData.value.goods_receipt?.number || "—",
}));

onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="p-10 font-sans text-sm text-gray-800">
        <!-- Header: Logo + Company Info -->
        <div class="flex items-start justify-between mb-10">
            <!-- Company Avatar -->
            <div
                class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center mr-6"
            >
                <img
                    v-if="modelData.company?.avatar"
                    :src="`/storage/${modelData.company.avatar}`"
                    alt="Company Logo"
                    class="w-full h-full object-cover"
                />
                <span v-else class="text-xl text-gray-500">No Logo</span>
            </div>

            <!-- Company Info -->
            <div class="flex-1 text-right">
                <h1 class="text-2xl font-bold">
                    {{ modelData.company?.name || "Company Name" }}
                </h1>
                <p class="text-sm text-gray-700">
                    {{ modelData.company?.address || "No address provided" }}
                </p>
                <p v-if="modelData.company?.email" class="text-sm text-gray-700">
                    Email: {{ modelData.company.email }}
                </p>
                <p v-if="modelData.company?.mobile" class="text-sm text-gray-700">
                    Mobile: {{ modelData.company.mobile }}
                </p>
                <p v-if="modelData.company?.landline" class="text-sm text-gray-700">
                    Landline: {{ modelData.company.landline }}
                </p>
                <p v-if="modelData.company?.website" class="text-sm text-gray-700">
                    Website: {{ modelData.company.website }}
                </p>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold mb-6">Supplier Invoice</h1>

        <!-- Invoice Info -->
        <div
            class="grid gap-6 mb-10"
            style="grid-template-columns: repeat(3, 1fr)"
        >
            <div
                v-for="(value, label) in basicInfo"
                :key="label"
                class="flex flex-col"
            >
                <span class="text-gray-500 text-xs uppercase font-medium">{{
                    label
                }}</span>
                <span class="text-gray-800 text-sm whitespace-pre-line capitalize">{{
                    value
                }}</span>
            </div>
        </div>

        <!-- Invoice Items -->
        <h2 class="text-lg font-semibold mb-2">Items</h2>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-2 py-1 text-left">Product</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">SKU</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Variation</th>
                    <th class="border border-gray-300 px-2 py-1 text-right">Quantity</th>
                    <th class="border border-gray-300 px-2 py-1 text-right">Unit Price</th>
                    <th class="border border-gray-300 px-2 py-1 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="detail in modelData.details" :key="detail.id">
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.supplier_product?.product?.name }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.supplier_product?.product?.sku }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1">Default</td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.quantity) }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.unit_price, { style: "currency", currency: "PHP" }) }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.total, { style: "currency", currency: "PHP" }) }}
                    </td>
                </tr>
                <tr v-if="!modelData.details?.length">
                    <td colspan="6" class="text-center text-gray-400 py-4">
                        No items found.
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="mt-4 flex justify-end">
            <div class="w-64">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-600 text-right">Subtotal:</div>
                    <div class="text-right font-medium">
                        {{ formatNumber(modelData.subtotal, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="text-gray-600 text-right">Tax Rate:</div>
                    <div class="text-right font-medium">
                        {{ modelData.tax_rate }}%
                    </div>

                    <div class="text-gray-600 text-right">Tax Amount:</div>
                    <div class="text-right font-medium">
                        {{ formatNumber(modelData.tax_amount, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="text-gray-600 text-right">Shipping Cost:</div>
                    <div class="text-right font-medium">
                        {{ formatNumber(modelData.shipping_cost, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="col-span-2 border-t border-gray-200 mt-2 pt-2">
                        <div class="flex justify-between">
                            <span class="text-gray-800 font-semibold">Total Amount:</span>
                            <span class="font-bold">
                                {{ formatNumber(modelData.total_amount, { style: "currency", currency: "PHP" }) }}
                            </span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-gray-800 font-semibold">Total Paid:</span>
                            <span class="font-bold text-green-600">
                                {{ formatNumber(totalPaid, { style: "currency", currency: "PHP" }) }}
                            </span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-gray-800 font-semibold">Balance:</span>
                            <span class="font-bold text-red-600">
                                {{ formatNumber(remainingBalance, { style: "currency", currency: "PHP" }) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notes -->
        <div class="mt-10 text-xs text-gray-600 leading-relaxed border-t pt-4 break-inside-avoid">
            <p v-if="modelData.remarks" class="mb-4">
                <strong>Remarks:</strong> {{ modelData.remarks }}
            </p>
            <p><strong>Note:</strong> This is a computer-generated document. No signature is required.</p>
            <p class="mt-2">For concerns, contact accounting at: <strong>{{ modelData.company?.email || 'support@example.com' }}</strong></p>
        </div>

        <!-- Signature Block -->
        <div class="mt-12 grid grid-cols-2 gap-20 text-sm">
            <div>
                <p class="mb-12">Prepared by:</p>
                <div class="border-t border-gray-400 w-full"></div>
            </div>
            <div>
                <p class="mb-12">Approved by:</p>
                <div class="border-t border-gray-400 w-full"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        margin: 0;
        padding: 0;
        font-size: 12px;
    }

    h1,
    h2 {
        margin-bottom: 10px;
    }

    .grid {
        break-inside: avoid;
    }

    .break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .text-green-600 {
        color: #059669 !important;
    }

    .text-red-600 {
        color: #DC2626 !important;
    }
}
</style> 