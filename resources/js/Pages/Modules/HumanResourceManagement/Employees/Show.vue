<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import ModalForm from "@/Components/Form/ModalForm.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import { useToast } from "vue-toastification";

const modelName = "employees";
const toast = useToast();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const companies = computed(() => page.props.companies || []);
const departments = computed(() => page.props.departments || []);

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

const employeeDetails = ref([
    { label: "Employee Number", value: "number" },
    { label: "Company", value: (row) => row.company?.name || "-" },
    { label: "Department", value: (row) => row.department?.name || "-" },
]);

const personalDetails = ref([
    { label: "First Name", value: "firstname" },
    { label: "Middle Name", value: "middlename" },
    { label: "Last Name", value: "lastname" },
    { label: "Suffix", value: "suffix" },
    { label: "Nickname", value: "nickname" },
    { label: "Gender", value: "gender" },
    {
        label: "Birthdate",
        value: (row) => moment(row.birthdate).format("MMMM D, YYYY"),
    },
    { label: "Birthplace", value: "birthplace" },
    { label: "Civil Status", value: "civil_status" },
    { label: "Citizenship", value: "citizenship" },
    { label: "Religion", value: "religion" },
]);

const vitalStatistics = ref([
    { label: "Blood Type", value: "blood_type" },
    { label: "Height", value: "height" },
    { label: "Weight", value: "weight" },
]);

const governmentDetails = ref([
    { label: "SSS Number", value: "sss" },
    { label: "PhilHealth Number", value: "philhealth" },
    { label: "Pag-IBIG Number", value: "pagibig" },
    { label: "TIN", value: "tin" },
    { label: "UMID", value: "umid" },
]);

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read employees",
    },
    {
        text: "Educational Attainments",
        url: `/${modelName}/${
            usePage().props.modelData.id
        }/educational-attainments`,
        inertia: true,
        permission: "read employee educational attainments",
    },
    {
        text: "Work Histories",
        url: `/${modelName}/${usePage().props.modelData.id}/work-histories`,
        inertia: true,
        permission: "read employee work histories",
    },
    {
        text: "Contact Details",
        url: `/${modelName}/${usePage().props.modelData.id}/contact-details`,
        inertia: true,
        permission: "read employee contact details",
    },
    {
        text: "Dependents",
        url: `/${modelName}/${usePage().props.modelData.id}/dependents`,
        inertia: true,
        permission: "read employee dependents",
    },
    {
        text: "Documents",
        url: `/${modelName}/${usePage().props.modelData.id}/documents`,
        inertia: true,
        permission: "read employee documents",
    },
    {
        text: "Certificates & Training",
        url: `/${modelName}/${usePage().props.modelData.id}/certificates`,
        inertia: true,
        permission: "read employee certificates",
    },
    {
        text: "Skills",
        url: `/${modelName}/${usePage().props.modelData.id}/skills`,
        inertia: true,
        permission: "read employee skills",
    },
]);

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

// Modal states
const showPersonalInfoModal = ref(false);
const showVitalStatisticsModal = ref(false);
const showGovernmentIdsModal = ref(false);
const showEmployeeDetailsModal = ref(false);

// Form fields for personal information
const personalInfoFields = [
    {
        id: "firstname",
        label: "First Name",
        model: "firstname",
        type: "text",
        placeholder: "Enter first name",
        required: true,
    },
    {
        id: "middlename",
        label: "Middle Name",
        model: "middlename",
        type: "text",
        placeholder: "Enter middle name",
        required: false,
    },
    {
        id: "lastname",
        label: "Last Name",
        model: "lastname",
        type: "text",
        placeholder: "Enter last name",
        required: true,
    },
    {
        id: "suffix",
        label: "Suffix",
        model: "suffix",
        type: "text",
        placeholder: "Enter suffix",
        required: false,
    },
    {
        id: "nickname",
        label: "Nickname",
        model: "nickname",
        type: "text",
        placeholder: "Enter nickname",
        required: false,
    },
    {
        id: "birthplace",
        label: "Birthplace",
        model: "birthplace",
        type: "text",
        placeholder: "Enter birthplace",
        required: false,
    },
    {
        id: "civil_status",
        label: "Civil Status",
        model: "civil_status",
        type: "select",
        options: [
            { value: "single", text: "Single" },
            { value: "married", text: "Married" },
            { value: "widowed", text: "Widowed" },
            { value: "separated", text: "Separated" },
        ],
        placeholder: "Select civil status",
        required: false,
    },
    {
        id: "citizenship",
        label: "Citizenship",
        model: "citizenship",
        type: "text",
        placeholder: "Enter citizenship",
        required: false,
    },
    {
        id: "religion",
        label: "Religion",
        model: "religion",
        type: "text",
        placeholder: "Enter religion",
        required: false,
    },
];

// Form fields for vital statistics
const vitalStatisticsFields = [
    {
        id: "blood_type",
        label: "Blood Type",
        model: "blood_type",
        type: "select",
        options: [
            { value: "A+", text: "A+" },
            { value: "A-", text: "A-" },
            { value: "B+", text: "B+" },
            { value: "B-", text: "B-" },
            { value: "AB+", text: "AB+" },
            { value: "AB-", text: "AB-" },
            { value: "O+", text: "O+" },
            { value: "O-", text: "O-" },
        ],
        placeholder: "Select blood type",
        required: false,
    },
    {
        id: "height",
        label: "Height",
        model: "height",
        type: "text",
        placeholder: "Enter height",
        required: false,
    },
    {
        id: "weight",
        label: "Weight",
        model: "weight",
        type: "text",
        placeholder: "Enter weight",
        required: false,
    },
];

// Form fields for government IDs
const governmentIdsFields = [
    {
        id: "sss",
        label: "SSS Number",
        model: "sss",
        type: "text",
        placeholder: "Enter SSS number",
        required: false,
    },
    {
        id: "philhealth",
        label: "PhilHealth Number",
        model: "philhealth",
        type: "text",
        placeholder: "Enter PhilHealth number",
        required: false,
    },
    {
        id: "pagibig",
        label: "Pag-IBIG Number",
        model: "pagibig",
        type: "text",
        placeholder: "Enter Pag-IBIG number",
        required: false,
    },
    {
        id: "tin",
        label: "TIN",
        model: "tin",
        type: "text",
        placeholder: "Enter TIN",
        required: false,
    },
    {
        id: "umid",
        label: "UMID",
        model: "umid",
        type: "text",
        placeholder: "Enter UMID",
        required: false,
    },
];

// Add employee fields for the modal
const employeeFields = [
    {
        id: "company_id",
        label: "Company",
        model: "company_id",
        type: "select",
        required: true,
        options: companies.value.map((company) => ({
            value: company.id,
            text: company.name.charAt(0).toUpperCase() + company.name.slice(1),
        })),
        placeholder: "Select company",
    },
    {
        id: "department_id",
        label: "Department",
        model: "department_id",
        type: "select",
        required: true,
        options: departments.value.map((department) => ({
            value: department.id,
            text:
                department.name.charAt(0).toUpperCase() +
                department.name.slice(1),
        })),
        placeholder: "Select department",
    },
];

const handleUpdate = async (data) => {
    try {
        await router.visit(`/${modelName}/${modelData.value.id}`);
        // toast.success("Updated successfully!");
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
            <NavigationTabs :tabs="navigationTabs" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <div class="relative">
                    <button
                        @click="showEmployeeDetailsModal = true"
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
                        title="Employee Information"
                        :modelData="modelData"
                        :rowDetails="employeeDetails"
                    />
                </div>

                <div class="border-t border-gray-200" />
                <div class="relative">
                    <button
                        @click="showPersonalInfoModal = true"
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
                        title="Personal Information"
                        :modelData="modelData"
                        :rowDetails="personalDetails"
                    />
                </div>

                <div class="border-t border-gray-200" />
                <div class="relative">
                    <button
                        @click="showVitalStatisticsModal = true"
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
                        title="Vital Statistics"
                        :modelData="modelData"
                        :rowDetails="vitalStatistics"
                    />
                </div>

                <div class="border-t border-gray-200" />
                <div class="relative">
                    <button
                        @click="showGovernmentIdsModal = true"
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
                        title="Government IDs"
                        :modelData="modelData"
                        :rowDetails="governmentDetails"
                    />
                </div>
            </div>
        </div>

        <!-- Employee Information Modal -->
        <ModalForm
            :show="showEmployeeDetailsModal"
            :title="'Edit Employee Information'"
            :fields="employeeFields"
            :modelData="modelData"
            :submitUrl="`/api/${modelName}/${modelData.id}`"
            method="put"
            @close="showEmployeeDetailsModal = false"
            @updated="handleUpdate"
        />

        <!-- Personal Information Modal -->
        <ModalForm
            :show="showPersonalInfoModal"
            :title="'Edit Personal Information'"
            :fields="personalInfoFields"
            :modelData="modelData"
            :submitUrl="`/api/${modelName}/${modelData.id}`"
            method="put"
            @close="showPersonalInfoModal = false"
            @updated="handleUpdate"
        />

        <!-- Vital Statistics Modal -->
        <ModalForm
            :show="showVitalStatisticsModal"
            :title="'Edit Vital Statistics'"
            :fields="vitalStatisticsFields"
            :modelData="modelData"
            :submitUrl="`/api/${modelName}/${modelData.id}`"
            method="put"
            @close="showVitalStatisticsModal = false"
            @updated="handleUpdate"
        />

        <!-- Government IDs Modal -->
        <ModalForm
            :show="showGovernmentIdsModal"
            :title="'Edit Government IDs'"
            :fields="governmentIdsFields"
            :modelData="modelData"
            :submitUrl="`/api/${modelName}/${modelData.id}`"
            method="put"
            @close="showGovernmentIdsModal = false"
            @updated="handleUpdate"
        />
    </AppLayout>
</template>
