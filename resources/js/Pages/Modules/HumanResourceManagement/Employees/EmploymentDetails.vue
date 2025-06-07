<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import ModalForm from "@/Components/Form/ModalForm.vue";
import { singularizeAndFormat, humanReadable } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import EmployeeTabs from "@/Components/Navigation/Tabs/EmployeeTabs.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

const modelName = "employees";
const toast = useToast();
const page = usePage();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const positions = computed(() => page.props.positions || []);
const supervisors = computed(() => page.props.supervisors || []);

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const getQrUrl = (id) => {
    return route("qr.employees", { employee: usePage().props.modelData.id });
};

const profileDetails = [
    { label: "Name", value: "full_name", class: "text-xl font-bold" },
    { label: "Number", value: "number", class: "text-gray-600 font-semibold" },
    { label: "Current Position", value: "current_position", class: "text-gray-600 font-semibold" },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.id),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const modelData = computed(() => page.props.modelData || {});

// Modal states
const showEmploymentDetailsModal = ref(false);

// Data states
const isLoading = ref(false);

// Add a computed property to get the latest employment details
const latestEmploymentDetails = computed(() => {
    if (!modelData.value.employment_details || !Array.isArray(modelData.value.employment_details)) {
        return null;
    }
    // Get the latest employment details record
    return modelData.value.employment_details[0] || null;
});

// Form data for employment details
const employmentDetailsForm = ref({
    employee_id: usePage().props.modelData.id,
    employment_status: latestEmploymentDetails.value?.employment_status || '',
    from_date: latestEmploymentDetails.value?.from_date || '',
    to_date: latestEmploymentDetails.value?.to_date || '',
    position_id: latestEmploymentDetails.value?.position_id || '',
    supervisor_id: latestEmploymentDetails.value?.supervisor_id || '',
    basic_salary: latestEmploymentDetails.value?.basic_salary || '',
    salary_type: latestEmploymentDetails.value?.salary_type || 'monthly',
    tax_status: latestEmploymentDetails.value?.tax_status || '',
    remarks: latestEmploymentDetails.value?.remarks || ''
});

// Employment details fields
const employmentDetailsFields = computed(() => [
    {
        id: "employment_status",
        label: "Employment Status",
        model: "employment_status",
        type: "select",
        required: true,
        options: [
            { value: "regular", text: "Regular" },
            { value: "probationary", text: "Probationary" },
            { value: "contractual", text: "Contractual" },
            { value: "part-time", text: "Part Time" },
            { value: "intern", text: "Intern" }
        ],
        placeholder: "Select employment status"
    },
    {
        id: "from_date",
        label: "From Date",
        model: "from_date",
        type: "date",
        required: true,
        placeholder: "Select start date"
    },
    {
        id: "to_date",
        label: "To Date",
        model: "to_date",
        type: "date",
        required: false,
        placeholder: "Select end date"
    },
    {
        id: "position_id",
        label: "Position",
        model: "position_id",
        type: "select",
        required: true,
        options: positions.value.map(position => ({
            value: position.id,
            text: position.name
        })),
        placeholder: "Select position"
    },
    {
        id: "supervisor_id",
        label: "Supervisor",
        model: "supervisor_id",
        type: "select",
        required: false,
        options: supervisors.value.map(supervisor => ({
            value: supervisor.id,
            text: supervisor.full_name
        })),
        placeholder: "Select supervisor (optional)"
    },
    {
        id: "basic_salary",
        label: "Basic Salary",
        model: "basic_salary",
        type: "number",
        required: true,
        placeholder: "Enter basic salary"
    },
    {
        id: "salary_type",
        label: "Salary Type",
        model: "salary_type",
        type: "select",
        required: true,
        options: [
            { value: "monthly", text: "Monthly" },
            { value: "semi-monthly", text: "Semi-Monthly" },
            { value: "weekly", text: "Weekly" },
            { value: "daily", text: "Daily" }
        ],
        placeholder: "Select salary type"
    },
    {
        id: "tax_status",
        label: "Tax Status",
        model: "tax_status",
        type: "select",
        required: true,
        options: [
            { value: "single", text: "Single" },
            { value: "married", text: "Married" },
            { value: "widowed", text: "Widowed" },
            { value: "separated", text: "Separated" },
            { value: "divorced", text: "Divorced" }
        ],
        placeholder: "Select tax status"
    },
    {
        id: "remarks",
        label: "Remarks",
        model: "remarks",
        type: "textarea",
        required: false,
        placeholder: "Enter remarks"
    }
]);

// Handle update employment details
const handleUpdate = async () => {
    try {
        await router.visit(`/employees/${modelData.value.id}/employment-details`);
    } catch (error) {
        console.error("Error updating:", error);
        toast.error("Something went wrong!");
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

        <div class="max-w-12xl mx-auto">
            <EmployeeTabs />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <div class="relative">
                    <button
                        @click="showEmploymentDetailsModal = true"
                        class="absolute right-4 top-4 p-2 text-gray-500 hover:text-gray-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                            />
                        </svg>
                    </button>

                    <DisplayInformation
                        title="Employment Details"
                        :modelData="latestEmploymentDetails"
                        :rowDetails="[
                            { label: 'Employment Status', value: (row) => row?.employment_status ? humanReadable(row.employment_status) : '-' },
                            { label: 'From Date', value: (row) => row?.from_date ? moment(row.from_date).format('MMMM D, YYYY') : '-' },
                            { label: 'To Date', value: (row) => row?.to_date ? moment(row.to_date).format('MMMM D, YYYY') : '-' },
                            { label: 'Position', value: (row) => row?.position?.name || '-' },
                            { label: 'Supervisor', value: (row) => row?.supervisor?.full_name || '-' },
                            { label: 'Basic Salary', value: (row) => row?.basic_salary ? `₱${parseFloat(row.basic_salary).toLocaleString()}` : '-' },
                            { label: 'Salary Type', value: (row) => row?.salary_type ? humanReadable(row.salary_type) : '-' },
                            { label: 'Tax Status', value: (row) => row?.tax_status ? humanReadable(row.tax_status) : '-' },
                            { label: 'Remarks', value: (row) => row?.remarks || '-' }
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Employment Details Modal -->
        <ModalForm
            :show="showEmploymentDetailsModal"
            :title="'Edit Employment Details'"
            :fields="employmentDetailsFields"
            :modelData="employmentDetailsForm"
            :submitUrl="`/api/${modelName}/${modelData.id}/employment-details`"
            method="put"
            @close="showEmploymentDetailsModal = false"
            @updated="handleUpdate"
        />
    </AppLayout>
</template>
