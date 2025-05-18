<script setup>
import { onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatNumber, humanReadable } from "@/utils/global";
import moment from "moment";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const formatDate = (date) => {
    return moment(date).format("MMMM D, YYYY");
};

const formatPaymentMethod = (method) => {
    const methods = {
        cash: "Cash",
        gcash: "GCash",
        "credit-card": "Credit Card",
        "bank-transfer": "Bank Transfer",
    };
    return methods[method] || method;
};

// Format serial numbers in a more compact way
const formatSerialNumbers = (serials) => {
    return serials.map(serial => {
        const productSerial = serial.warehouse_product_serial;
        const parts = [];
        
        // Add serial/batch number
        parts.push(productSerial.serial_number);
        if (productSerial.batch_number) {
            parts.push(`Batch: ${productSerial.batch_number}`);
        }
        
        // Add dates if present
        const dates = [];
        if (productSerial.manufactured_at) {
            dates.push(`Mfg: ${formatDate(productSerial.manufactured_at)}`);
        }
        if (productSerial.expired_at) {
            dates.push(`Exp: ${formatDate(productSerial.expired_at)}`);
        }
        
        return {
            main: parts.join(' | '),
            dates: dates.join(' | ')
        };
    });
};

onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="p-10 font-sans text-sm text-gray-800">
        <!-- Header: Logo + Company Info -->
        <div class="flex items-start justify-between mb-10">
            <!-- Company Info -->
            <div>
                <h1 class="text-2xl font-bold">
                    {{ modelData.company?.name }}
                </h1>
                <p class="text-gray-600">{{ modelData.company?.address }}</p>
                <p class="text-gray-600">{{ modelData.company?.mobile }}</p>
                <p class="text-gray-600">{{ modelData.company?.email }}</p>
            </div>
            <!-- Invoice Info -->
            <div class="text-right">
                <h2 class="text-xl font-bold text-gray-800 mb-2">
                    SALES INVOICE
                </h2>
                <p class="text-gray-600">
                    Invoice #:
                    <span class="font-semibold">{{ modelData.number }}</span>
                </p>
                <p class="text-gray-600">
                    Date:
                    <span class="font-semibold">{{
                        formatDate(modelData.invoice_date)
                    }}</span>
                </p>
                <p class="text-gray-600">
                    Due Date:
                    <span class="font-semibold">{{
                        modelData.due_date
                            ? formatDate(modelData.due_date)
                            : "-"
                    }}</span>
                </p>
            </div>
        </div>

        <!-- Bill To & Ship To Section -->
        <div class="grid grid-cols-2 gap-8 py-8 border-b border-gray-200">
            <div>
                <h3 class="font-semibold text-gray-800 mb-3">Bill To:</h3>
                <p class="font-medium text-gray-800">
                    {{ modelData.customer?.name }}
                </p>
                <p class="text-gray-600">{{ modelData.customer?.address }}</p>
                <p class="text-gray-600">{{ modelData.customer?.phone }}</p>
                <p class="text-gray-600">{{ modelData.customer?.email }}</p>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 mb-3">Ship From:</h3>
                <p class="font-medium text-gray-800">
                    {{ modelData.warehouse?.name }}
                </p>
                <p class="text-gray-600">{{ modelData.warehouse?.address }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mt-8">
            <thead>
                <tr class="text-left border-b-2 border-gray-200">
                    <th class="pb-4 text-gray-600 text-sm font-semibold">
                        Item Description
                    </th>
                    <th
                        class="pb-4 text-gray-600 text-sm font-semibold text-right"
                    >
                        Unit
                    </th>
                    <th
                        class="pb-4 text-gray-600 text-sm font-semibold text-right"
                    >
                        Qty
                    </th>
                    <th
                        class="pb-4 text-gray-600 text-sm font-semibold text-right"
                    >
                        Price
                    </th>
                    <th
                        class="pb-4 text-gray-600 text-sm font-semibold text-right"
                    >
                        Amount
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="detail in modelData.details"
                    :key="detail.id"
                    class="border-b border-gray-100"
                >
                    <td class="py-4">
                        <p class="font-medium text-gray-800">
                            {{
                                detail.warehouse_product
                                    ?.supplier_product_detail?.product?.name
                            }}
                        </p>
                        <div v-if="detail.invoice_serials?.length" class="text-sm text-gray-500 mt-2 space-y-1">
                            <div v-for="(serial, index) in formatSerialNumbers(detail.invoice_serials)" :key="index" class="pl-4 border-l border-gray-200">
                                <p class="font-medium">{{ serial.main }}</p>
                                <p class="text-xs text-gray-400">{{ serial.dates }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 text-right">
                        {{
                            detail.warehouse_product?.supplier_product_detail
                                ?.product?.unit_of_measure
                        }}
                    </td>
                    <td class="py-4 text-right">{{ detail.qty }}</td>
                    <td class="py-4 text-right">
                        {{
                            formatNumber(detail.price, {
                                style: "currency",
                                currency: modelData.currency,
                            })
                        }}
                    </td>
                    <td class="py-4 text-right">
                        {{
                            formatNumber(detail.total, {
                                style: "currency",
                                currency: modelData.currency,
                            })
                        }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="mt-8 grid grid-cols-2 gap-8">
            <!-- Payment Information -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-3">
                    Payment Information
                </h3>
                <div class="space-y-2">
                    <p class="text-gray-600">
                        Method:
                        <span class="font-medium">{{
                            formatPaymentMethod(
                                modelData.payment_method_details?.[0]
                                    ?.payment_method
                            )
                        }}</span>
                    </p>
                    <template
                        v-if="
                            modelData.payment_method_details?.[0]
                                ?.account_number
                        "
                    >
                        <p class="text-gray-600">
                            Account:
                            <span class="font-medium">{{
                                modelData.payment_method_details[0]
                                    .account_number
                            }}</span>
                        </p>
                    </template>
                    <p class="text-gray-600">
                        Status:
                        <span class="font-medium capitalize">{{
                            humanReadable(modelData.status)
                        }}</span>
                    </p>
                </div>
            </div>

            <!-- Totals -->
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium">{{
                        formatNumber(modelData.subtotal, {
                            style: "currency",
                            currency: modelData.currency,
                        })
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600"
                        >VAT ({{ modelData.tax_rate }}%):</span
                    >
                    <span class="font-medium">{{
                        formatNumber(modelData.tax_amount, {
                            style: "currency",
                            currency: modelData.currency,
                        })
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Discount:</span>
                    <span class="font-medium text-green-600"
                        >-{{
                            formatNumber(modelData.discount_amount, {
                                style: "currency",
                                currency: modelData.currency,
                            })
                        }}</span
                    >
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Shipping:</span>
                    <span class="font-medium">{{
                        formatNumber(modelData.shipping_cost, {
                            style: "currency",
                            currency: modelData.currency,
                        })
                    }}</span>
                </div>
                <div class="flex justify-between pt-3 border-t border-gray-200">
                    <span class="font-semibold text-gray-800">Total:</span>
                    <span class="font-bold text-gray-800">{{
                        formatNumber(modelData.total_amount, {
                            style: "currency",
                            currency: modelData.currency,
                        })
                    }}</span>
                </div>
            </div>
        </div>

        <!-- Signature Block -->
        <div class="mt-12 grid grid-cols-2 gap-20 text-sm">
            <div>
                <p class="mb-12">Prepared by:</p>
                <div class="border-t border-gray-400 w-full"></div>
                <p class="mt-2">
                    {{ page.props.auth?.user?.name || "_____________________" }}
                </p>
                <p class="mt-1 text-gray-500">
                    {{ page.props.auth?.user?.email }}
                </p>
            </div>
            <div>
                <p class="mb-12">Received by:</p>
                <div class="border-t border-gray-400 w-full"></div>
                <p class="mt-2">
                    {{ modelData.customer?.name || "_____________________" }}
                </p>
                <p class="mt-1 text-gray-500">
                    {{ modelData.customer?.email }}
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-center text-gray-500 text-sm border-t pt-8">
            <p>Thank you for your business!</p>
            <p class="mt-1">
                For questions about this invoice, please contact
                {{ modelData.company?.name }}
            </p>
            <p class="mt-1">
                {{ modelData.company?.email }} | {{ modelData.company?.mobile }}
            </p>
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

    .p-10 {
        padding: 2.5rem;
    }

    h1,
    h2 {
        margin-bottom: 10px;
    }

    .grid {
        break-inside: avoid;
    }

    table {
        break-inside: auto;
    }

    tr {
        break-inside: avoid;
        break-after: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }

    /* Add styles for serial numbers in print */
    .border-l {
        border-left-width: 1px !important;
        border-color: #e5e7eb !important;
        margin-left: 0.5rem !important;
    }

    .pl-4 {
        padding-left: 1rem !important;
    }

    .space-y-1 > * + * {
        margin-top: 0.25rem !important;
    }

    .text-xs {
        font-size: 0.75rem !important;
    }

    .text-gray-400 {
        color: #9ca3af !important;
    }
}
</style>
