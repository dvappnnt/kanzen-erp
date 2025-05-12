<script setup>
import { onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatNumber, formatDate } from "@/utils/global";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const purchaseOrderDetails = computed(() => page.props.purchaseOrderDetails || []);

// Add computed property for subtotal
const subtotal = computed(() => {
    return purchaseOrderDetails.value.reduce((sum, detail) => {
        return sum + (Number(detail.total) || 0);
    }, 0);
});

const basicInfo = computed(() => ({
    Number: modelData.value.number,
    Company: modelData.value.company?.name || "—",
    Supplier: modelData.value.supplier?.name || "—",
    Warehouse: modelData.value.warehouse?.name || "—",
    Status: modelData.value.status,
    "Order Date": modelData.value.order_date
        ? formatDate("M d Y", modelData.value.order_date)
        : "—",
    "Expected Delivery": modelData.value.expected_delivery_date
        ? formatDate("M d Y", modelData.value.expected_delivery_date)
        : "—",
    "Payment Terms": modelData.value.payment_terms || "—",
    "Shipping Terms": modelData.value.shipping_terms || "—",
    Notes: modelData.value.notes || "—",
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
        <h1 class="text-2xl font-bold mb-6">Purchase Order</h1>

        <!-- Purchase Order Info -->
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
                <span class="text-gray-800 text-sm whitespace-pre-line">{{
                    value
                }}</span>
            </div>
        </div>

        <!-- Purchase Order Items -->
        <h2 class="text-lg font-semibold mb-2">Items</h2>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-2 py-1 text-left">
                        Product
                    </th>
                    <th class="border border-gray-300 px-2 py-1 text-left">
                        Variation
                    </th>
                    <th class="border border-gray-300 px-2 py-1 text-center">
                        Qty
                    </th>
                    <th class="border border-gray-300 px-2 py-1 text-center">
                        Free
                    </th>
                    <th class="border border-gray-300 px-2 py-1 text-right">
                        Price
                    </th>
                    <th class="border border-gray-300 px-2 py-1 text-right">
                        Total
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="detail in purchaseOrderDetails" :key="detail.id">
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.supplier_product_detail?.product?.name || "N/A" }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.supplier_product_detail?.variation?.name || "N/A" }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        {{ detail.qty }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        {{ detail.free_qty }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.price, { style: "currency", currency: "PHP" }) }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.total, { style: "currency", currency: "PHP" }) }}
                    </td>
                </tr>

                <tr v-if="purchaseOrderDetails.length === 0">
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
                        {{ formatNumber(subtotal, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="text-gray-600 text-right">Tax Rate:</div>
                    <div class="text-right font-medium">
                        {{ modelData.tax_rate }}%
                    </div>

                    <div class="text-gray-600 text-right">Tax Amount:</div>
                    <div class="text-right font-medium">
                        {{ formatNumber(modelData.tax_amount || 0, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="text-gray-600 text-right">Shipping Cost:</div>
                    <div class="text-right font-medium">
                        {{ formatNumber(modelData.shipping_cost || 0, { style: "currency", currency: "PHP" }) }}
                    </div>

                    <div class="col-span-2 border-t border-gray-200 mt-2 pt-2">
                        <div class="flex justify-between">
                            <span class="text-gray-800 font-semibold">Total Amount:</span>
                            <span class="font-bold">
                                {{ formatNumber(modelData.total_amount || 0, { style: "currency", currency: "PHP" }) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notes -->
        <div class="mt-10 text-xs text-gray-600 leading-relaxed border-t pt-4 break-inside-avoid">
            <p><strong>Note:</strong> This purchase order is subject to our company's terms and conditions. Delivery must occur on or before the expected date. Report any discrepancies within 24 hours of receipt.</p>
            <p class="mt-2">For concerns, contact purchasing at: <strong>{{ modelData.company?.email || 'support@example.com' }}</strong>.</p>
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
}
</style>
