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
import { singularizeAndFormat } from "@/utils/global";
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
const banks = computed(() => page.props.banks || []);

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
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.id),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

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

const modelData = computed(() => page.props.modelData || {});

// Modal states
const showPayrollDetailsModal = ref(false);

// Data states
const isLoading = ref(false);

// Add a computed property to get the latest payroll details
const latestPayrollDetails = computed(() => {
    if (!modelData.value.payroll_details || !Array.isArray(modelData.value.payroll_details)) {
        return null;
    }
    // Get the latest payroll details record
    return modelData.value.payroll_details[0] || null;
});

// Form data for payroll details
const payrollDetailsForm = ref({
    employee_id: usePage().props.modelData.id,
    bank_id: latestPayrollDetails.value?.bank_id || '',
    payroll_type: latestPayrollDetails.value?.payroll_type || '',
    account_number: latestPayrollDetails.value?.account_number || '',
    account_name: latestPayrollDetails.value?.account_name || ''
});

// Payroll details fields
const payrollDetailsFields = computed(() => [
    {
        id: "bank_id",
        label: "Bank",
        model: "bank_id",
        type: "select",
        required: true,
        options: banks.value.map(bank => ({
            value: bank.id,
            text: bank.name
        })),
        placeholder: "Select bank"
    },
    {
        id: "payroll_type",
        label: "Payroll Type",
        model: "payroll_type",
        type: "select",
        required: true,
        options: [
            { value: "ATM", text: "ATM" },
            { value: "CASH", text: "Cash" },
            { value: "CHECK", text: "Check" }
        ],
        placeholder: "Select payroll type"
    },
    {
        id: "account_number",
        label: "Account Number",
        model: "account_number",
        type: "text",
        required: true,
        placeholder: "Enter account number"
    },
    {
        id: "account_name",
        label: "Account Name",
        model: "account_name",
        type: "text",
        required: true,
        placeholder: "Enter account name"
    }
]);

// Handle update
const handleUpdate = async () => {
    try {
        await router.visit(`/employees/${modelData.value.id}/payroll-details`);
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
                        @click="showPayrollDetailsModal = true"
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
                        title="Payroll Details"
                        :modelData="latestPayrollDetails"
                        :rowDetails="[
                            { label: 'Bank', value: (row) => row?.bank?.name || '-' },
                            { label: 'Payroll Type', value: (row) => row?.payroll_type || '-' },
                            { label: 'Account Number', value: (row) => row?.account_number || '-' },
                            { label: 'Account Name', value: (row) => row?.account_name || '-' }
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Payroll Details Modal -->
        <ModalForm
            :show="showPayrollDetailsModal"
            :title="'Edit Payroll Details'"
            :fields="payrollDetailsFields"
            :modelData="payrollDetailsForm"
            :submitUrl="`/api/${modelName}/${modelData.id}/payroll-details`"
            method="put"
            @close="showPayrollDetailsModal = false"
            @updated="handleUpdate"
        />
    </AppLayout>
</template>
