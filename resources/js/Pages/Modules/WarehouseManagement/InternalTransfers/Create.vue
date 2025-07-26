<template>
    <AppLayout title="Create Internal Transfer">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Create Internal Transfer</h2>
            <form @submit.prevent="submit">
                <!-- Reference No (Disabled) -->
                <div class="mb-4">
                    <label class="block font-medium mb-1">Reference No</label>
                    <input
                        v-model="form.reference_no"
                        type="text"
                        class="input bg-gray-100"
                        disabled
                    />
                </div>

                <!-- Hidden: material_request_id -->
                <input type="hidden" v-model="form.material_request_id" />

                <!-- From Warehouse (Auto-filled) -->
                <div class="mb-4">
                    <label class="block font-medium mb-1">From Warehouse</label>
                    <select
                        v-model="form.from_warehouse_id"
                        class="input bg-gray-100"
                        disabled
                    >
                        <option
                            v-for="wh in warehouses"
                            :key="wh.id"
                            :value="wh.id"
                        >
                            {{ wh.name }}
                        </option>
                    </select>
                </div>

                <!-- To Warehouse (Selectable) -->
                <div class="mb-4">
                    <label class="block font-medium mb-1">To Warehouse</label>
                    <select v-model="form.to_warehouse_id" class="input">
                        <option value="" disabled>Select warehouse</option>
                        <option
                            v-for="wh in warehouses"
                            :key="wh.id"
                            :value="wh.id"
                        >
                            {{ wh.name }}
                        </option>
                    </select>
                </div>

                <!-- Remarks -->
                <div class="mb-4">
                    <label class="block font-medium mb-1">Remarks</label>
                    <textarea
                        v-model="form.remarks"
                        class="input"
                        placeholder="Optional..."
                    ></textarea>
                </div>
                <!-- Requested Items with Transfer Qty -->
                <div class="mb-6">
                    <label class="block font-medium mb-2"
                        >Items to Transfer</label
                    >
                    <table class="table-auto w-full border">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-3 py-2">Item</th>
                                <th class="border px-3 py-2">Requested Qty</th>
                                <th class="border px-3 py-2">
                                    Qty to Transfer
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in selectedRequest?.items || []"
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
                                        min="1"
                                        :max="item.requested_qty"
                                        v-model.number="form.items[item.id]"
                                        class="input w-24"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Submit -->
                <PrimaryButton type="submit">Create</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const { props } = usePage();
const warehouses = props.warehouses;
const selectedRequest = props.selected_material_request;

const form = useForm({
    reference_no: selectedRequest?.reference_no || "",
    material_request_id: selectedRequest?.id || "",
    from_warehouse_id: selectedRequest?.warehouse?.id || "",
    to_warehouse_id: "",
    remarks: "",
    status: "pending",
    items: {},
});

const submit = () => {
    form.post("/internal-transfers");
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
