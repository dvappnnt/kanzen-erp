<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    thresholds: Object,
    warehouses: Array,
    products: Array,
    filters: Object, // Add this to receive from controller
});

const filters = ref({
    warehouse_id: props.filters?.warehouse_id || '',
    product_id: props.filters?.product_id || '',
});

const applyFilters = () => {
    router.get(route('stock-alert-thresholds.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Modal state
const isOpen = ref(false);
const selectedThreshold = ref(null);
const form = useForm({
    min_qty: '',
});

const openModal = (threshold) => {
    selectedThreshold.value = threshold;
    form.min_qty = threshold.min_qty;
    isOpen.value = true;
};

const updateMinQty = () => {
    form.put(route('stock-alert-thresholds.update', selectedThreshold.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Stock Alert Thresholds" />
    <AppLayout title="Stock Alert Thresholds">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Stock Alert Thresholds
                </h2>
                <Link
                    href="/stock-alert-thresholds/create"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                >
                    + Create Threshold
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filter -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Warehouse</label>
                            <select v-model="filters.warehouse_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">All Warehouses</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product</label>
                            <select v-model="filters.product_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">All Products</option>
                                <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="threshold in thresholds.data" :key="threshold.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ threshold.warehouse?.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ threshold.product?.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ threshold.min_qty }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="openModal(threshold)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="thresholds.data.length === 0">
                                <td colspan="4" class="text-center text-gray-500 py-4">
                                    No threshold records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <DialogModal :show="isOpen" @close="isOpen = false">
            <template #title>Edit Minimum Quantity</template>
            <template #content>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-1">
                        <strong>Warehouse:</strong> {{ selectedThreshold?.warehouse?.name }}
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        <strong>Product:</strong> {{ selectedThreshold?.product?.name }}
                    </p>

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
            </template>
            <template #footer>
                <PrimaryButton @click="updateMinQty" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Update
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
