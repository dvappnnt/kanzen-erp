<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import SelectInput from "@/Components/Form/SelectInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    threshold: Object,
    warehouses: Array,
    products: Array,
});

const warehouseOptions = props.warehouses.map(w => ({
    label: w.name,
    value: String(w.id),
}));

const productOptions = props.products.map(p => ({
    label: p.name,
    value: String(p.id),
}));

const form = useForm({
    warehouse_id: String(props.threshold.warehouse_id),
    product_id: String(props.threshold.product_id),
    min_qty: props.threshold.min_qty,
});

const submit = () => {
    form.put(route("stock-alert-thresholds.update", props.threshold.id));
};
</script>

<template>
    <AppLayout title="Edit Stock Alert Threshold">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Stock Alert Threshold
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="warehouse_id" value="Warehouse" />
                                <SelectInput v-model="form.warehouse_id">
                                    <option value="">Select Warehouse</option>
                                    <option v-for="w in warehouseOptions" :key="w.value" :value="w.value">
                                        {{ w.label }}
                                    </option>
                                </SelectInput>
                                <InputError :message="form.errors.warehouse_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="product_id" value="Product" />
                                <SelectInput v-model="form.product_id">
                                    <option value="">Select Product</option>
                                    <option v-for="p in productOptions" :key="p.value" :value="p.value">
                                        {{ p.label }}
                                    </option>
                                </SelectInput>
                                <InputError :message="form.errors.product_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="min_qty" value="Minimum Quantity" />
                                <TextInput
                                    id="min_qty"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.min_qty"
                                    min="0"
                                />
                                <InputError :message="form.errors.min_qty" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
