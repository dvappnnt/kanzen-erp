<template>
    <AppLayout :title="`Edit ${form.reference_no}`">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Edit Internal Transfer</h2>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label class="block font-medium mb-1">Reference No</label>
                    <input
                        v-model="form.reference_no"
                        type="text"
                        class="input"
                    />
                </div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">From Warehouse</label>
                    <select v-model="form.from_warehouse_id" class="input">
                        <option
                            v-for="wh in warehouses"
                            :key="wh.id"
                            :value="wh.id"
                        >
                            {{ wh.name }}
                        </option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">To Warehouse</label>
                    <select v-model="form.to_warehouse_id" class="input">
                        <option
                            v-for="wh in warehouses"
                            :key="wh.id"
                            :value="wh.id"
                        >
                            {{ wh.name }}
                        </option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">Remarks</label>
                    <textarea v-model="form.remarks" class="input"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">Status</label>
                    <select v-model="form.status" class="input">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="transferred">Transferred</option>
                    </select>
                </div>

                <!-- Items Table -->
                <div class="mb-4">
                    <label class="block font-medium mb-2"
                        >Requested Items</label
                    >
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
                            <tr
                                v-for="item in transfer.material_request.items"
                                :key="item.id"
                            >
                                <td class="border px-3 py-2">
                                    {{ item.product.name }}
                                </td>
                                <td class="border px-3 py-2">
                                    {{ item.requested_qty }}
                                </td>
                                <td class="border px-3 py-2">
                                    <input
                                        type="number"
                                        v-model="form.items[item.id]"
                                        class="w-full border px-2 py-1 rounded"
                                        min="0"
                                        :max="item.requested_qty"
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
import { useForm, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useToast } from "vue-toastification";

const { props } = usePage();
const transfer = props.transfer;
const warehouses = props.warehouses;
const toast = useToast();

const form = useForm({
    reference_no: transfer.reference_no,
    from_warehouse_id: transfer.from_warehouse_id,
    to_warehouse_id: transfer.to_warehouse_id,
    remarks: transfer.remarks,
     status: transfer.status,
    items: Object.fromEntries(
        transfer.items.map((item) => [
            item.request_item.id,
            item.quantity
        ])
    ),
});

const submit = () => {
    form.put(`/internal-transfers/${transfer.id}`, {
        onSuccess: () => {
            toast.success('Internal transfer updated successfully!');
        },
        onError: () => {
            toast.error('Something went wrong. Please try again.');
        },
    });
};

</script>

<style scoped>
.input {
    border: 1px solid #ccc;
    padding: 8px;
    border-radius: 4px;
    width: 100%;
}
</style>
