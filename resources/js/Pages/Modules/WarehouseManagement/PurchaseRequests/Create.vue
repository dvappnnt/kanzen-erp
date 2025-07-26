<template>
    <AppLayout title="Create Purchase Request">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Create Purchase Request</h2>
            <form @submit.prevent="submit" class="space-y-4">

                <!-- Material Request Selection -->
                <div>
                    <label class="block font-medium mb-1">Material Request</label>
                    <select
                        v-model="form.material_request_id"
                        :disabled="!!pre_selected_material_request_id"
                        class="w-full border rounded px-3 py-2"
                    >
                        <option
                            v-for="mr in material_requests"
                            :value="mr.id"
                            :key="mr.id"
                        >
                            {{ mr.reference_no }}
                        </option>
                    </select>
                </div>

                <!-- Warehouse -->
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

                <!-- Remarks -->
                <div>
                    <label class="block font-medium mb-1">Remarks</label>
                    <textarea
                        v-model="form.remarks"
                        class="w-full border rounded px-3 py-2"
                    ></textarea>
                </div>

                <!-- Items Table -->
                <div v-if="selectedMaterialRequestItems.length > 0">
                    <label class="block font-medium mb-1">Items</label>
                    <table class="w-full border text-sm mb-4">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-2 py-1 text-left">Item</th>
                                <th class="border px-2 py-1 text-left">Requested Qty</th>
                                <th class="border px-2 py-1 text-left">Qty to Purchase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedMaterialRequestItems" :key="item.id">
                                <td class="border px-2 py-1">{{ item.product.name }}</td>
                                <td class="border px-2 py-1">{{ item.requested_qty }}</td>
                                <td class="border px-2 py-1">
                                    <input
                                        type="number"
                                        min="0"
                                        :max="item.requested_qty"
                                        v-model.number="form.items[item.id]"
                                        class="border px-2 py-1 w-full"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Submit -->
                <PrimaryButton type="submit">Submit</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>


<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref, watch } from "vue";

const { material_requests, warehouses, pre_selected_material_request_id } =
    usePage().props;

const selectedMaterialRequestId = ref(pre_selected_material_request_id || "");
const selectedMaterialRequestItems = ref([]);

const form = useForm({
    material_request_id: selectedMaterialRequestId.value,
    warehouse_id: "",
    remarks: "",
    items: {}, // { material_request_item_id: qty }
});

// Watch for material request change and load items
watch(
    selectedMaterialRequestId,
    async (id) => {
        if (!id) return;
        const res = await axios.get(`/material-requests/${id}/items`);
        selectedMaterialRequestItems.value = res.data.items;

        // Initialize items form values
        form.items = {};
        for (const item of res.data.items) {
            form.items[item.id] = 0;
        }

        // Update form value too
        form.material_request_id = id;
    },
    { immediate: true }
);

const submit = () => {
    form.post("/purchase-requests");
};
</script>
