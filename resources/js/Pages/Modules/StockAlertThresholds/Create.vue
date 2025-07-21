<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import BoundSelectInput from '@/Components/Form/BoundSelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import HeaderActions from '@/Components/HeaderActions.vue';

const props = defineProps({
    warehouses: Array,
    products: Array,
});

const warehouseOptions = props.warehouses.map(w => ({
    label: w.name,
    value: w.id,
}));

const productOptions = props.products.map(p => ({
    label: p.name,
    value: p.id,
}));

const form = useForm({
    warehouse_id: '',
    product_id: '',
    min_qty: '',
});

const submit = () => {
    form.post(route('stock-alert-thresholds.store'));
};
const modelName = "stock-alert-thresholds";
const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);
</script>

<template>
    <AppLayout title="Create Stock Alert Threshold">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                     Create Stock Alert Threshold
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="warehouse_id" value="Warehouse" />
                                <BoundSelectInput v-model="form.warehouse_id">
                                    <option value="">Select Warehouse</option>
                                    <option v-for="w in warehouseOptions" :key="w.value" :value="w.value">
                                        {{ w.label }}
                                    </option>
                                </BoundSelectInput>
                                <InputError :message="form.errors.warehouse_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="product_id" value="Product" />
                                <BoundSelectInput v-model="form.product_id">
                                    <option value="">Select Product</option>
                                    <option v-for="p in productOptions" :key="p.value" :value="p.value">
                                        {{ p.label }}
                                    </option>
                                </BoundSelectInput>
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
                                Save
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
