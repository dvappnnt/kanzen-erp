<template>
    <AppLayout :title="`View ${transfer.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">
                Internal Transfer: {{ transfer.reference_no }}
            </h2>
            <p>
                <strong>From Warehouse:</strong>
                {{ transfer.from_warehouse?.name }}
            </p>
            <p>
                <strong>To Warehouse:</strong> {{ transfer.to_warehouse?.name }}
            </p>
            <p><strong>Status:</strong> {{ transfer.status }}</p>
            <p><strong>Remarks:</strong> {{ transfer.remarks }}</p>
            <p>
                <strong>Created At:</strong>
                {{ new Date(transfer.created_at).toLocaleString() }}
            </p>

            <!-- Requested Items Table -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-2">Items</h3>
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left">Item</th>
                            <th class="border px-3 py-2 text-left">
                                Requested Qty
                            </th>
                            <th class="border px-3 py-2 text-left">
                                To Transfer Qty
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in transfer.items" :key="item.id">
                            <td class="border px-3 py-2">
                                {{ item.request_item?.product?.name || "N/A" }}
                            </td>
                            <td class="border px-3 py-2">
                                {{ item.request_item?.requested_qty || "N/A" }}
                            </td>
                            <td class="border px-3 py-2">
                                {{ item.quantity }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";

const { props } = usePage();
const transfer = props.transfer;
</script>
