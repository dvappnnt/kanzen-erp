<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
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
const showViewModal = ref(false);
const modalType = ref(""); // 'add', 'delete', 'view'
const currentImage = ref(null);

const formData = ref({
    name: "",
    description: "",
    file_path: null
});

// Storage URL handling
const storageUrl = computed(() => {
    return window.location.origin + '/storage/';
});

const getImageUrl = (path) => {
    if (!path) return '';
    return path.startsWith('http') ? path : storageUrl.value + path;
};

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
const images = computed(() => page.props.images || []);

const openModal = (type, image = null) => {
    modalType.value = type;
    currentImage.value = image;
    if (type === "add") {
        formData.value = { name: "", description: "", file_path: null };
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    modalType.value = "";
    currentImage.value = null;
    formData.value = { name: "", description: "", file_path: null };
};

const openViewModal = (image) => {
    currentImage.value = image;
    modalType.value = 'view';
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    modalType.value = "";
    currentImage.value = null;
};

const handleFileUpload = (event) => {
    formData.value.file_path = event.target.files;
};

const handleSubmit = async () => {
    try {
        isSubmitting.value = true;
        
        // Handle multiple files
        const files = formData.value.file_path;
        const uploadPromises = [];

        for (let i = 0; i < files.length; i++) {
            const formDataObj = new FormData();
            formDataObj.append('file_path', files[i]);
            formDataObj.append('name', formData.value.name);
            formDataObj.append('description', formData.value.description);
            formDataObj.append('product_id', modelData.value.id);

            uploadPromises.push(
                axios.post('/api/product-images', formDataObj, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
            );
        }

        await Promise.all(uploadPromises);
        toast.success("Images uploaded successfully!");
        closeModal();
        router.get(`/${modelName}/${modelData.value.id}/images`);
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
        await axios.delete(`/api/product-images/${currentImage.value.id}`);
        toast.success("Image deleted successfully!");
        closeModal();
        router.get(`/${modelName}/${modelData.value.id}/images`);
    } catch (error) {
        toast.error("Something went wrong!");
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
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
                        <h2 class="text-lg font-semibold">Product Images</h2>
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
                            Add Image
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="image in images"
                            :key="image.id"
                            class="relative group rounded-lg overflow-hidden border hover:shadow-md transition cursor-pointer"
                            @click="openViewModal(image)"
                        >
                            <img
                                :src="getImageUrl(image.file_path)"
                                :alt="`Product Image ${image.id}`"
                                class="w-full h-48 object-cover"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <div class="flex space-x-2">
                                    <button
                                        @click.stop="openModal('delete', image)"
                                        class="p-2 rounded-full bg-white text-red-500 hover:bg-red-50 transition-colors"
                                        title="Delete"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ modalType === "add" ? "Add Images" : "Delete Image" }}
                </h2>

                <div v-if="modalType === 'add'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="formData.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter image name"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Description
                            <span class="text-gray-500 ml-1">(optional)</span>
                        </label>
                        <textarea
                            v-model="formData.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter image description"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Images <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="file"
                            @change="handleFileUpload"
                            accept="image/*"
                            multiple
                            class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-[var(--primary-color)] file:text-white
                                hover:file:bg-opacity-90"
                            :style="{
                                '--primary-color': buttonPrimaryBgColor,
                            }"
                            required
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            You can select multiple images at once
                        </p>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">
                    Are you sure you want to delete this image? This action cannot be undone.
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
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </span>
                        {{ modalType === "delete" ? "Delete" : "Upload" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- View Modal -->
        <Modal :show="showViewModal" @close="closeViewModal">
            <div class="p-6">
                <div class="flex flex-col items-center">
                    <img
                        :src="getImageUrl(currentImage?.file_path)"
                        :alt="currentImage?.name"
                        class="w-full max-h-96 object-contain rounded-lg mb-4"
                    />
                    <div class="w-full space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Name</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ currentImage?.name }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Description</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ currentImage?.description || 'No description provided' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">File Path</h3>
                            <p class="mt-1 text-sm text-gray-900 break-all">{{ currentImage?.file_path }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeViewModal"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
