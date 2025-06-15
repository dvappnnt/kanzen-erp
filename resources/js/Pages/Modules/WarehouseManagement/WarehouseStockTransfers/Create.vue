<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch, reactive } from "vue";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import Modal from "@/Components/Modal.vue";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "warehouse-stock-transfers";
const isSubmitting = ref(false);
const toast = useToast();
const currentStep = ref(1);

const step1Data = reactive({
    origin_warehouse_id: "",
    destination_warehouse_id: "",
    transfer_date: "",
});

const warehouses = ref([]);
const originWarehouse = ref(null);
const destinationWarehouse = ref(null);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const filterDestinationWarehouses = (warehouses) => {
    if (!step1Data.origin_warehouse_id) return warehouses;
    return warehouses.filter(w => w.id !== step1Data.origin_warehouse_id);
};

const mapWarehouseData = (data) => data;
const handleOriginWarehouseSelect = (response) => {
    console.log('Origin select response:', response);
    if (response?.data?.[0]) {
        step1Data.origin_warehouse_id = response.data[0].id;
        originWarehouse.value = response.data[0];
        console.log('Origin warehouse selected:', step1Data.origin_warehouse_id);
    }
};
const handleDestinationWarehouseSelect = (response) => {
    console.log('Destination select response:', response);
    if (response?.data?.[0]) {
        step1Data.destination_warehouse_id = response.data[0].id;
        destinationWarehouse.value = response.data[0];
    }
};

const canProceedStep1 = computed(() => {
    return (
        step1Data.origin_warehouse_id &&
        step1Data.destination_warehouse_id &&
        String(step1Data.origin_warehouse_id) !== String(step1Data.destination_warehouse_id) &&
        step1Data.transfer_date
    );
});

const nextStep = () => {
    if (!canProceedStep1.value) {
        toast.error("Please complete all fields and select different warehouses.");
        return;
    }
    currentStep.value = 2;
};
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const finalizeTransfer = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    try {
        const payload = {
            origin_warehouse_id: step1Data.origin_warehouse_id,
            destination_warehouse_id: step1Data.destination_warehouse_id,
            transfer_date: step1Data.transfer_date,
            status: 'pending',
        };
        const res = await axios.post("/api/warehouse-stock-transfers", payload);
        toast.success("Stock transfer created successfully");
        router.get(`/warehouse-stock-transfers/${res.data.data.id}`);
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to create stock transfer");
    } finally {
        isSubmitting.value = false;
    }
};

// ...template below...
</script>

<template>
    <AppLayout :title="`Create ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create {{ singularizeAndFormat(modelName) }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stepper -->
            <div class="mb-8">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="relative">
                        <!-- Progress Line -->
                        <div class="absolute top-5 left-0 w-full h-1 bg-gray-200">
                            <div class="h-1 transition-all duration-300" :style="{ 'background-color': buttonPrimaryBgColor, width: `${((currentStep - 1) / 1) * 100}%` }"></div>
                        </div>
                        <!-- Steps -->
                        <div class="relative flex justify-between">
                            <div v-for="(step, index) in ['Select Warehouses', 'Review Stock Transfer']" :key="index" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium relative bg-white border-2 transition-all duration-300"
                                    :style="{
                                        'border-color': index + 1 <= currentStep ? buttonPrimaryBgColor : 'rgb(209 213 219)',
                                        'background-color': index + 1 < currentStep ? buttonPrimaryBgColor : 'white',
                                        color: index + 1 < currentStep ? buttonPrimaryTextColor : index + 1 === currentStep ? buttonPrimaryBgColor : 'rgb(107 114 128)',
                                    }">
                                    <span v-if="index + 1 < currentStep">✓</span>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="mt-2 text-sm text-center transition-all duration-300"
                                    :style="{
                                        color: index + 1 === currentStep ? buttonPrimaryBgColor : index + 1 < currentStep ? 'rgb(17 24 39)' : 'rgb(107 114 128)',
                                        'font-weight': index + 1 === currentStep ? '500' : '400',
                                    }">
                                    {{ step }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Step 1: Select Warehouses and Date -->
            <div v-if="currentStep === 1" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin Warehouse <span class="text-red-500">*</span></label>
                        <Autocomplete search-url="/api/autocomplete/warehouses" placeholder="Search for origin warehouse..." model-name="warehouses" :map-custom-buttons="mapWarehouseData" @select="handleOriginWarehouseSelect" class="w-full" />
                        <div v-if="originWarehouse" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                            <span class="font-medium">Selected:</span> {{ originWarehouse.name }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Destination Warehouse <span class="text-red-500">*</span></label>
                        <Autocomplete search-url="/api/autocomplete/warehouses" placeholder="Search for destination warehouse..." model-name="warehouses" :map-custom-buttons="mapWarehouseData" @select="handleDestinationWarehouseSelect" class="w-full" :filter-results="filterDestinationWarehouses" />
                        <div v-if="destinationWarehouse" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                            <span class="font-medium">Selected:</span> {{ destinationWarehouse.name }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Date <span class="text-red-500">*</span></label>
                        <input type="date" v-model="step1Data.transfer_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="nextStep" class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white" :class="{ 'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80': true }" :style="{ '--primary-color': buttonPrimaryBgColor }" :disabled="!canProceedStep1">
                        Next
                    </button>
                </div>
            </div>
            <!-- Step 2: Review Stock Transfer -->
            <div v-if="currentStep === 2" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Review Transfer</h3>
                    <ul class="text-sm space-y-2">
                        <li><span class="font-semibold">Origin Warehouse:</span> {{ originWarehouse ? originWarehouse.name : '' }}</li>
                        <li><span class="font-semibold">Destination Warehouse:</span> {{ destinationWarehouse ? destinationWarehouse.name : '' }}</li>
                        <li><span class="font-semibold">Transfer Date:</span> {{ step1Data.transfer_date }}</li>
                    </ul>
                </div>
            <div class="mt-6 flex justify-between">
                    <button @click="prevStep" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                    <button @click="finalizeTransfer" class="ml-auto px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" :disabled="isSubmitting">
                        <span v-if="isSubmitting" class="animate-spin mr-2">⌛</span>
                        {{ isSubmitting ? "Creating..." : "Create Stock Transfer" }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
