<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import { parseInput } from "@/utils/parseInput";

const page = usePage();
const modelName = "employees";
const isSubmitting = ref(false);
const toast = useToast();

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const companies = computed(() =>
    Array.isArray(page.props.companies) ? page.props.companies : []
);

const departments = ref([]);

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const fetchDepartments = async (companyId) => {
    try {
        const response = await axios.get(`/api/departments?company_id=${companyId}`);
        departments.value = response.data.data;
    } catch (error) {
        console.error('Error fetching departments:', error);
        departments.value = [];
    }
};

const fields = computed(() => [
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
            text: department.name.charAt(0).toUpperCase() + department.name.slice(1),
        })),
        placeholder: "Select department",
    },
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
        id: "birthdate",
        label: "Birthdate",
        model: "birthdate",
        type: "date",
        placeholder: "Select birthdate",
        required: false,
    },
    {
        id: "gender",
        label: "Gender",
        model: "gender",
        type: "select",
        options: [
            { value: "male", text: "Male" },
            { value: "female", text: "Female" },
        ],
        placeholder: "Select gender",
        required: false,
    },
    {
        id: "avatar",
        label: "Avatar",
        model: "avatar",
        type: "file",
        placeholder: "Upload avatar",
        required: false,
    },
]);

const formData = ref({
    company_id: page.props.auth?.user?.company_id,
});

// Watch for company_id changes
watch(() => formData.value.company_id, (newCompanyId) => {
    if (newCompanyId) {
        fetchDepartments(newCompanyId);
        // Reset department_id when company changes
        formData.value.department_id = null;
    } else {
        departments.value = [];
        formData.value.department_id = null;
    }
});

// Fetch departments for initial company value
onMounted(() => {
    if (formData.value.company_id) {
        fetchDepartments(formData.value.company_id);
    }
});

const errors = ref({});

const submitForm = async () => {
    try {
        isSubmitting.value = true;

        const formDataObj = parseInput(fields.value, formData.value);
        const response = await axios.post(`/api/${modelName}`, formDataObj, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });
        toast.success("Submitted successfully!");
        const modelDataId = response.data.modelData.id;
        router.get(`/${modelName}/${modelDataId}`);
    } catch (error) {
        toast.error("Something went wrong!");
        if (error.response && error.response.data.errors) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Create ${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-3xl">
            <FormSetup
                :form-classes="'md:grid md:grid-cols-1 md:gap-2'"
                :col-span="'md:col-span-1'"
                @submitted="submitForm"
            >
                <template #title>
                    Create a new
                    {{ singularizeAndFormat(modelName).toLowerCase() }}
                </template>
                <template #description>
                    <p>
                        Fill in the form below to create a new
                        {{ singularizeAndFormat(modelName).toLowerCase() }}.
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="text-red-500 font-semibold">*</span>
                        Fields with this mark are required.
                    </p>
                </template>

                <!-- Dynamic Form Fields -->
                <template #form>
                    <div v-for="field in fields" :key="field.id">
                        <FormField
                            :id="field.id"
                            :label="field.label"
                            :type="field.type"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            :input-classes="field.class"
                            :options="field.options || []"
                            v-model="formData[field.model]"
                        />
                        <!-- InputError component for showing field-specific errors -->
                        <InputError :message="errors[field.model]" />
                    </div>
                </template>

                <template #actions>
                    <button
                        type="submit"
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
                        <span v-if="!isSubmitting">Save</span>
                        <span v-else>Submitting...</span>
                    </button>
                </template>
            </FormSetup>
        </div>
    </AppLayout>
</template>
