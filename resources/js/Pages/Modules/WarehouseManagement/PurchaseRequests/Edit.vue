<template>
    <AppLayout :title="`Edit Purchase Request ${request.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Edit Purchase Request</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Warehouse</label>
                    <select
                        v-model="form.warehouse_id"
                        class="w-full border rounded px-3 py-2"
                    >
                        <option
                            v-for="wh in warehouses"
                            :value="wh.id"
                            :key="wh.id"
                        >
                            {{ wh.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium mb-1">Status</label>
                    <select
                        v-model="form.status"
                        class="w-full border rounded px-3 py-2"
                    >
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium mb-1">Remarks</label>
                    <textarea
                        v-model="form.remarks"
                        class="w-full border rounded px-3 py-2"
                    ></textarea>
                </div>

                <div v-if="request.items.length">
                    <label class="block font-medium mb-1">Items</label>
                    <table class="w-full border text-sm mb-4">
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
                                        item.material_request_item?.product
                                            ?.name ?? "-"
                                    }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{
                                        item.material_request_item
                                            ?.requested_qty ?? 0
                                    }}
                                </td>
                                <td class="border px-2 py-1">
                                    <input
                                        type="number"
                                        min="0"
                                        :max="
                                            item.material_request_item
                                                ?.requested_qty ?? 0
                                        "
                                        class="border px-2 py-1 w-full"
                                        v-model.number="
                                            form.items[
                                                item.material_request_item_id
                                            ]
                                        "
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <PrimaryButton type="submit">Update</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useToast } from "vue-toastification";
const toast = useToast();
const { request, warehouses } = usePage().props;

const form = useForm({
    warehouse_id: request.warehouse_id,
    status: request.status,
    remarks: request.remarks,
    items: Object.fromEntries(
        request.items.map((i) => [i.material_request_item_id, i.quantity])
    ),
});

const submit = () => {
    form.put(`/purchase-requests/${request.id}`, {
        onSuccess: () => {
            toast.success("Purchase Request updated successfully!");
        },
    });
};
</script>
