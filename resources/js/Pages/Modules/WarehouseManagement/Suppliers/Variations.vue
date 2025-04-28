<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";

const modelName = "products";
const page = usePage();
const toast = useToast();
const isSubmitting = ref(false);
const showModal = ref(false);
const modalType = ref(""); // 'add', 'edit', 'delete'
const currentVariation = ref(null);

const formData = ref({
    sku: "",
    price: "",
    stock: "",
    attributes: {}
});

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const getQrUrl = (id) => {
    return route("qr.products", { product: usePage().props.modelData.id });
};

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    {
        label: "Category",
        value: (row) => row.category.name,
        class: "text-gray-600 font-semibold",
    },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.token),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read suppliers",
    },
    {
        text: "Images Information",
        url: `/${modelName}/${usePage().props.modelData.id}/images`,
        inertia: true,
        permission: "read product images",
    },
    {
        text: "Specifications Information",
        url: `/${modelName}/${usePage().props.modelData.id}/specifications`,
        inertia: true,
        permission: "read product specifications",
    },
    {
        text: "Variations Information",
        url: `/${modelName}/${usePage().props.modelData.id}/variations`,
        inertia: true,
        permission: "read product variations",
    },
]);

const modelData = computed(() => page.props.modelData || {});
const variations = computed(() => page.props.variations || []);
const attributes = computed(() => page.props.attributes || []);

const openModal = (type, variation = null) => {
    modalType.value = type;
    currentVariation.value = variation;
    if (type === "edit" && variation) {
        formData.value = {
            sku: variation.sku,
            price: variation.price,
            stock: variation.stock,
            attributes: {}
        };
        // Set the selected attribute values
        variation.attributes.forEach(attr => {
            formData.value.attributes[attr.attribute_id] = attr.attribute_value_id;
        });
    } else {
        formData.value = {
            sku: "",
            price: "",
            stock: "",
            attributes: {}
        };
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    modalType.value = "";
    currentVariation.value = null;
    formData.value = {
        sku: "",
        price: "",
        stock: "",
        attributes: {}
    };
};

const handleSubmit = async () => {
    try {
        isSubmitting.value = true;
        const url =
            modalType.value === "add"
                ? `/api/products/${modelData.value.id}/variations`
                : `/api/products/${modelData.value.id}/variations/${currentVariation.value.id}`;

        const method = modalType.value === "add" ? "post" : "put";

        const data = {
            ...formData.value,
            attributes: Object.entries(formData.value.attributes).map(([attributeId, valueId]) => ({
                attribute_id: parseInt(attributeId),
                attribute_value_id: valueId
            }))
        };

        await axios[method](url, data);

        toast.success(
            `${modalType.value === "add" ? "Added" : "Updated"} successfully!`
        );
        closeModal();
        // Refresh the page to get updated data
        router.get(`/${modelName}/${modelData.value.id}/variations`);
    } catch (error) {
        toast.error("Something went wrong!");
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

const handleDelete = async () => {
    try {
        isSubmitting.value = true;
        await axios.delete(
            `/api/products/${modelData.value.id}/variations/${currentVariation.value.id}`
        );
        toast.success("Deleted successfully!");
        closeModal();
        // Refresh the page to get updated data
        router.get(`/${modelName}/${modelData.value.id}/variations`);
    } catch (error) {
        toast.error("Something went wrong!");
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

const getAttributeValue = (variation, attributeId) => {
    const attribute = variation.attributes.find(attr => attr.attribute_id === attributeId);
    return attribute ? attribute.value : '-';
};
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <NavigationTabs :tabs="navigationTabs" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />

                <div class="px-9 py-9">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Variations</h2>
                        <button
                            @click="openModal('add')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                            :class="{
                                'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                            }"
                            :style="{
                                '--primary-color': buttonPrimaryBgColor,
                                color: buttonPrimaryTextColor,
                            }"
                        >
                            Add Variation
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="variation in variations"
                            :key="variation.id"
                            class="flex items-center justify-between p-4 border rounded-lg hover:shadow-md transition group bg-white"
                        >
                            <div class="flex flex-col w-2/3">
                                <div class="text-sm font-semibold text-gray-800">
                                    SKU: {{ variation.sku }}
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    Price: {{ variation.price }} | Stock: {{ variation.stock }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    <span v-for="attr in variation.attributes" :key="attr.id" class="mr-2">
                                        {{ attr.name }}: {{ attr.value }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click="openModal('edit', variation)"
                                    class="text-gray-400 hover:text-blue-500 transition-colors"
                                    title="Edit"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        ></path>
                                    </svg>
                                </button>

                                <button
                                    @click="openModal('delete', variation)"
                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                    title="Delete"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{
                        modalType === "add"
                            ? "Add Variation"
                            : modalType === "edit"
                            ? "Edit Variation"
                            : "Delete Variation"
                    }}
                </h2>

                <div v-if="modalType !== 'delete'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            SKU *
                        </label>
                        <input
                            type="text"
                            v-model="formData.sku"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        >
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Price *
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="formData.price"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Stock *
                            </label>
                            <input
                                type="number"
                                v-model="formData.stock"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                        </div>
                    </div>
                    <div v-for="attr in attributes" :key="attr.id" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ attr.name }} *
                        </label>
                        <select
                            v-model="formData.attributes[attr.id]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        >
                            <option value="">Select {{ attr.name }}</option>
                            <option v-for="value in attr.values" :key="value.id" :value="value.id">
                                {{ value.value }}
                            </option>
                        </select>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">
                    Are you sure you want to delete this variation? This action cannot be undone.
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeModal"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="modalType === 'delete' ? handleDelete() : handleSubmit()"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                        :class="{
                            'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                        }"
                        :style="{
                            '--primary-color': buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                        :disabled="isSubmitting"
                    >
                        <span v-if="isSubmitting" class="animate-spin mr-2">
                            <svg
                                class="w-4 h-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8z"
                                ></path>
                            </svg>
                        </span>
                        {{ modalType === "delete" ? "Delete" : "Save" }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
