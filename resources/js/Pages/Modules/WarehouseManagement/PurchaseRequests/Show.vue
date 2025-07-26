<template>
    <AppLayout :title="`Purchase Request ${request.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Purchase Request Details</h2>
            <p><strong>Reference No:</strong> {{ request.reference_no }}</p>
            <p>
                <strong>Material Request:</strong>
                {{ request.material_request?.reference_no }}
            </p>
            <p><strong>Warehouse:</strong> {{ request.warehouse?.name }}</p>
            <p><strong>Status:</strong> {{ request.status }}</p>
            <p><strong>Remarks:</strong> {{ request.remarks }}</p>

            <div v-if="request.items.length" class="mt-6">
                <h3 class="text-lg font-semibold mb-2">Items</h3>
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-2 py-1 text-left">Item</th>
                            <th class="border px-2 py-1 text-left">
                                Requested Qty
                            </th>
                            <th class="border px-2 py-1 text-left">
                                Qty to Purchase
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in request.items" :key="item.id">
                            <td class="border px-2 py-1">
                                {{
                                    item.material_request_item?.product?.name ??
                                    "-"
                                }}
                            </td>
                            <td class="border px-2 py-1">
                                {{
                                    item.material_request_item?.requested_qty ??
                                    0
                                }}
                            </td>
                            <td class="border px-2 py-1">
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
import { usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const { request } = usePage().props;
</script>
