<script setup>
import { useForm, usePage, router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const toast = useToast();
const { props } = usePage();
const request = props.request;
const warehouses = props.warehouses;

const form = useForm({
    warehouse_id: request.warehouse_id,
    status: request.status,
    items: request.items.map(item => ({
        id: item.id,
        product_name: item.product?.name ?? '',
        requested_qty: item.requested_qty,
        
    })),
});

const updateRequest = () => {
    router.put(`/material-requests/${request.id}`, form, {
        onSuccess: () => {
            toast.success("✅ Material Request updated successfully!");
        },
        onError: () => {
            toast.error("❌ Failed to update Material Request.");
        },
    });
};
</script>

<template>
    <AppLayout :title="`Edit ${request.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">
                Edit Material Request: {{ request.reference_no }}
            </h2>

            <form @submit.prevent="updateRequest" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Warehouse</label>
                    <select v-model="form.warehouse_id" class="border rounded px-3 py-2 w-full">
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                            {{ wh.name }}
                        </option>
                    </select>
                    <div v-if="form.errors.warehouse_id" class="text-red-500 text-sm">
                        {{ form.errors.warehouse_id }}
                    </div>
                </div>

                <div>
                    <label class="block font-medium mb-1">Status</label>
                    <select v-model="form.status" class="border rounded px-3 py-2 w-full">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div v-if="form.errors.status" class="text-red-500 text-sm">
                        {{ form.errors.status }}
                    </div>
                </div>

                <div>
                    <label class="block font-medium mb-2">Requested Items</label>
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.id"
                        class="mb-4 border p-3 rounded"
                    >
                        <p><strong>Product:</strong> {{ item.product_name }}</p>

                        <div class="mt-2">
                            <label class="block text-sm">Quantity</label>
                            <input
                                v-model="item.requested_qty"
                                type="number"
                                min="1"
                                class="border rounded px-2 py-1 w-full"
                            />
                        </div>

                      
                    </div>
                </div>

                <PrimaryButton type="submit">
                    Update Request
                </PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
