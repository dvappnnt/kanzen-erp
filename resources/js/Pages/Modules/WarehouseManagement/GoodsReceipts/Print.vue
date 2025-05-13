<script setup>
import { onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatNumber, formatDate } from "@/utils/global";
import moment from "moment";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const basicInfo = computed(() => ({
    "Number": modelData.value.number,
    "Purchase Order": modelData.value.purchase_order?.number || "—",
    "Date": modelData.value.date
        ? moment(modelData.value.date).format('MMM D, YYYY')
        : "—",
    "Status": modelData.value.status,
    "Notes": modelData.value.notes || "—",
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
        <h1 class="text-2xl font-bold mb-6">Goods Receipt</h1>

        <!-- Basic Info -->
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

        <!-- Items Table -->
        <h2 class="text-lg font-semibold mb-2">Details</h2>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-2 py-1 text-left">Product</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Variation</th>
                    <th class="border border-gray-300 px-2 py-1 text-right">Expected Qty</th>
                    <th class="border border-gray-300 px-2 py-1 text-right">Received Qty</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="detail in modelData.details" :key="detail.id">
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.purchase_order_detail?.supplier_product_detail?.product?.name || 'N/A' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1">
                        {{ detail.purchase_order_detail?.supplier_product_detail?.variation?.name || 'N/A' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.expected_qty, { minimumFractionDigits: 0 }) }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-right">
                        {{ formatNumber(detail.received_qty, { minimumFractionDigits: 0 }) }}
                    </td>
                </tr>
                <tr v-if="!modelData.details?.length">
                    <td colspan="4" class="text-center text-gray-400 py-4">
                        No items found
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Serial/Batch Numbers Table -->
        <h2 class="text-lg font-semibold mb-2 mt-8">Serial/Batch Numbers</h2>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-2 py-1 text-left">Product</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Serial Number</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Batch Number</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Manufactured Date</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="detail in modelData.details" :key="detail.id">
                    <tr v-for="serial in detail.serials" :key="serial.id">
                        <td class="border border-gray-300 px-2 py-1">
                            {{ detail.purchase_order_detail?.supplier_product_detail?.product?.name || 'N/A' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-1">
                            {{ serial.serial_number || '-' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-1">
                            {{ serial.batch_number || '-' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-1">
                            {{ serial.manufactured_at ? moment(serial.manufactured_at).format('MMM D, YYYY') : '-' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-1">
                            {{ serial.expired_at ? moment(serial.expired_at).format('MMM D, YYYY') : '-' }}
                        </td>
                    </tr>
                </template>
                <tr v-if="!modelData.details?.some(detail => detail.serials?.length > 0)">
                    <td colspan="5" class="text-center text-gray-400 py-4">
                        No serial/batch numbers found
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Notes -->
        <div class="mt-10 text-xs text-gray-600 leading-relaxed border-t pt-4 break-inside-avoid">
            <p><strong>Note:</strong> This is a computer-generated document. No signature is required.</p>
            <p class="mt-2">For concerns, contact warehouse at: <strong>{{ modelData.company?.email || 'support@example.com' }}</strong></p>
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