<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import { debounce } from "lodash";

const modelName = "employee-dependents";
const toast = useToast();
const page = usePage();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

// Data and loading states
const dependents = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const pagination = ref({
    current_page: 1,
    total: 0,
    per_page: 10,
    last_page: 0
});

// Modal states
const showCreateModal = ref(false);
const showViewModal = ref(false);
const showEditModal = ref(false);
const selectedRow = ref(null);

// Form data
const formData = ref({
    employee_id: usePage().props.modelData.id,
    name: '',
    mobile: '',
    landline: '',
    address: '',
    email: '',
    relationship: '',
    birthdate: '',
    birthplace: '',
    is_emergency_contact: false
});

// Header actions
const headerActions = ref([
    {
        text: "Go Back",
        url: `/employees/${usePage().props.modelData.id}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
    {
        text: "Add Dependent",
        type: "button",
        onClick: () => {
            resetForm();
            showCreateModal.value = true;
        },
        class: "bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded text-white",
    },
]);

// Navigation tabs
const navigationTabs = ref([
    {
        text: "Overview",
        url: `/employees/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read employees",
    },
    {
        text: "Employment Details",
        url: `/employees/${usePage().props.modelData.id}/employment-details`,
        inertia: true,
        permission: "read employee employment details",
    },
    {
        text: "Payroll Details",
        url: `/employees/${usePage().props.modelData.id}/payroll-details`,
        inertia: true,
        permission: "read employee payroll details",
    },
    {
        text: "Work Experiences",
        url: `/employees/${
            usePage().props.modelData.id
        }/work-experiences`,
        inertia: true,
        permission: "read employee work experiences",
    },
    {
        text: "Educational Attainments",
        url: `/employees/${
            usePage().props.modelData.id
        }/educational-attainments`,
        inertia: true,
        permission: "read employee educational attainments",
    },
    {
        text: "Work Histories",
        url: `/employees/${usePage().props.modelData.id}/work-histories`,
        inertia: true,
        permission: "read employee work histories",
    },
    {
        text: "Contact Details",
        url: `/employees/${usePage().props.modelData.id}/contact-details`,
        inertia: true,
        permission: "read employee contact details",
    },
    {
        text: "Dependents",
        url: `/employees/${usePage().props.modelData.id}/dependents`,
        inertia: true,
        permission: "read employee dependents",
    },
    {
        text: "Documents",
        url: `/employees/${usePage().props.modelData.id}/documents`,
        inertia: true,
        permission: "read employee documents",
    },
    {
        text: "Certificates & Training",
        url: `/employees/${usePage().props.modelData.id}/certificates`,
        inertia: true,
        permission: "read employee certificates",
    },
    {
        text: "Skills",
        url: `/employees/${usePage().props.modelData.id}/skills`,
        inertia: true,
        permission: "read employee skills",
    },
    {
        text: "Disciplinary Actions",
        url: `/employees/${usePage().props.modelData.id}/disciplinary-actions`,
        inertia: true,
        permission: "read employee disciplinary actions",
    },
]);

// Profile details
const profileDetails = [
    { label: "Name", value: "full_name", class: "text-xl font-bold" },
    { label: "Number", value: "number", class: "text-gray-600 font-semibold" },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.id),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

// Fetch data
const fetchData = async (page = 1) => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/${modelName}`, {
            params: {
                page,
                employee_id: usePage().props.modelData.id,
                search: searchQuery.value
            }
        });
        
        dependents.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            total: response.data.total,
            per_page: response.data.per_page,
            last_page: response.data.last_page
        };
    } catch (error) {
        console.error("Error fetching data:", error);
        toast.error("Failed to load dependents");
    } finally {
        isLoading.value = false;
    }
};

// Handle search
const handleSearch = () => {
    pagination.value.current_page = 1; // Reset to first page when searching
    fetchData();
};

// Debounced search
const debouncedSearch = debounce(handleSearch, 300);

// Watch for search query changes
watch(searchQuery, () => {
    debouncedSearch();
});

// Handle create
const handleCreate = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(`/api/${modelName}`, formData.value);
        await fetchData();
        showCreateModal.value = false;
        resetForm();
        toast.success("Dependent created successfully");
    } catch (error) {
        console.error("Error creating:", error);
        toast.error(error.response?.data?.message || "Failed to create dependent");
    } finally {
        isLoading.value = false;
    }
};

// Handle update
const handleUpdate = async () => {
    try {
        isLoading.value = true;
        const response = await axios.put(`/api/${modelName}/${selectedRow.value.id}`, formData.value);
        await fetchData();
        showEditModal.value = false;
        resetForm();
        toast.success("Dependent updated successfully");
    } catch (error) {
        console.error("Error updating:", error);
        toast.error(error.response?.data?.message || "Failed to update dependent");
    } finally {
        isLoading.value = false;
    }
};

// Handle delete
const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this dependent?')) {
        return;
    }

    try {
        isLoading.value = true;
        await axios.delete(`/api/${modelName}/${id}`);
        await fetchData();
        toast.success("Dependent deleted successfully");
    } catch (error) {
        console.error("Error deleting:", error);
        toast.error(error.response?.data?.message || "Failed to delete dependent");
    } finally {
        isLoading.value = false;
    }
};

// Handle view
const handleView = (row) => {
    selectedRow.value = row;
    showViewModal.value = true;
};

// Handle edit
const handleEdit = (row) => {
    selectedRow.value = row;
    formData.value = {
        ...row,
        birthdate: row.birthdate ? moment(row.birthdate).format('YYYY-MM-DD') : '',
        is_emergency_contact: row.is_emergency_contact
    };
    showEditModal.value = true;
};

// Reset form
const resetForm = () => {
    formData.value = {
        employee_id: usePage().props.modelData.id,
        name: '',
        mobile: '',
        landline: '',
        address: '',
        email: '',
        relationship: '',
        birthdate: '',
        birthplace: '',
        is_emergency_contact: false
    };
    selectedRow.value = null;
};

// Get QR URL
const getQrUrl = (id) => {
    return route("qr.employees", { employee: usePage().props.modelData.id });
};

// Initialize
onMounted(() => fetchData());
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

        <div class="max-w-12xl mx-auto">
            <NavigationTabs :tabs="navigationTabs" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="usePage().props.modelData"
                />
                <ProfileCard
                    :modelData="usePage().props.modelData"
                    :columns="profileDetails"
                />

                <div class="border-t border-gray-200" />

                <!-- Dependents Table -->
                <div class="p-6">
                    <!-- Search Input -->
                    <div class="mb-4">
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                class="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                placeholder="Search dependents..."
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Relationship</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Birthdate</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Emergency Contact</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in dependents" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ item.name }}</td>
                                    <td class="px-3 py-2">{{ item.relationship }}</td>
                                    <td class="px-3 py-2">{{ moment(item.birthdate).format('MMMM D, YYYY') }}</td>
                                    <td class="px-3 py-2">
                                        <div class="text-sm">
                                            <p v-if="item.mobile">Mobile: {{ item.mobile }}</p>
                                            <p v-if="item.landline">Landline: {{ item.landline }}</p>
                                            <p v-if="item.email">Email: {{ item.email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span 
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                item.is_emergency_contact 
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            ]"
                                        >
                                            {{ item.is_emergency_contact ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex justify-center space-x-2">
                                            <button
                                                @click="handleView(item)"
                                                class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50"
                                                title="View"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="handleEdit(item)"
                                                class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                title="Edit"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="handleDelete(item.id)"
                                                class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="dependents.length === 0">
                                    <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                                        No dependents found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button
                                @click="fetchData(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </button>
                            <button
                                @click="fetchData(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
                                    to
                                    <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                                    of
                                    <span class="font-medium">{{ pagination.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <button
                                        v-for="page in pagination.last_page"
                                        :key="page"
                                        @click="fetchData(page)"
                                        :class="[
                                            page === pagination.current_page
                                                ? 'relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                                : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Dependent</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="handleCreate" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                v-model="formData.name"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                v-model="formData.relationship"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Birthdate <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date"
                                v-model="formData.birthdate"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Birthplace</label>
                            <input 
                                type="text"
                                v-model="formData.birthplace"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mobile</label>
                            <input 
                                type="text"
                                v-model="formData.mobile"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Landline</label>
                            <input 
                                type="text"
                                v-model="formData.landline"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input 
                                type="email"
                                v-model="formData.email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea 
                                v-model="formData.address"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                        </div>

                        <div class="flex items-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox"
                                    v-model="formData.is_emergency_contact"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Emergency Contact</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            :disabled="isLoading"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                            :disabled="isLoading"
                        >
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Modal -->
        <div v-if="showViewModal && selectedRow" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">View Dependent</h3>
                    <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Relationship</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.relationship }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Birthdate</label>
                            <p class="mt-1 text-sm text-gray-900">{{ moment(selectedRow.birthdate).format('MMMM D, YYYY') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Birthplace</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.birthplace || '-' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Mobile</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.mobile || '-' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Landline</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.landline || '-' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.email || '-' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Address</label>
                            <p class="mt-1 text-sm text-gray-900">{{ selectedRow.address || '-' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Emergency Contact</label>
                            <p class="mt-1">
                                <span 
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        selectedRow.is_emergency_contact 
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    ]"
                                >
                                    {{ selectedRow.is_emergency_contact ? 'Yes' : 'No' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button 
                        @click="showViewModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal && selectedRow" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Dependent</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="handleUpdate" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                v-model="formData.name"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                v-model="formData.relationship"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Birthdate <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date"
                                v-model="formData.birthdate"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Birthplace</label>
                            <input 
                                type="text"
                                v-model="formData.birthplace"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mobile</label>
                            <input 
                                type="text"
                                v-model="formData.mobile"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Landline</label>
                            <input 
                                type="text"
                                v-model="formData.landline"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input 
                                type="email"
                                v-model="formData.email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea 
                                v-model="formData.address"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                        </div>

                        <div class="flex items-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox"
                                    v-model="formData.is_emergency_contact"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Emergency Contact</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            :disabled="isLoading"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                            :disabled="isLoading"
                        >
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
