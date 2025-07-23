<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";

const { props } = usePage();
const request = props.request;
</script>

<template>
    <AppLayout :title="`View ${request.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">
                Material Request: {{ request.reference_no }}
            </h2>

            <p><strong>Warehouse:</strong> {{ request.warehouse?.name }}</p>
            <p><strong>Requested By:</strong> {{ request.requested_by?.name }}</p>
            <p><strong>Status:</strong> {{ request.status }}</p>
            <p><strong>Date Created:</strong> {{ moment(request.created_at).format("LLLL") }}</p>

            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-2">Requested Items</h3>
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 text-left">Product</th>
                            <th class="border px-4 py-2 text-left">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in request.items"
                            :key="item.id"
                        >
                            <td class="border px-4 py-2">{{ item.product?.name ?? '—' }}</td>
                            <td class="border px-4 py-2">{{ item.requested_qty }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!request.items.length" class="text-gray-500 mt-2">No products added.</p>
            </div>
        </div>
    </AppLayout>
</template>

