<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { parseInput } from "@/utils/parseInput";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const modelName = "company-accounts";
const page = usePage();
const isSubmitting = ref(false);
const toast = useToast();

const formData = ref({
    company_id: page.props.auth?.user?.company_id,
    status: "Active",
    balance: 0.00,
    currency: "PHP",
});
const errors = ref({}); // Object to hold error messages

const companies = computed(() =>
    Array.isArray(page.props.companies) ? page.props.companies : []
);

const banks = computed(() =>
    Array.isArray(page.props.banks) ? page.props.banks : []
);

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

const fields = ref([
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
    },
    {
        id: "bank_id",
        label: "Bank",
        model: "bank_id",
        type: "select",
        required: true,
        options: banks.value.map((bank) => ({
            value: bank.id,
            text: bank.name.charAt(0).toUpperCase() + bank.name.slice(1),
        })),
    },
    {
        id: "name",
        label: "Account Name",
        model: "name",
        type: "text",
        required: true,
        placeholder: "Enter account name (e.g., Payroll Account)",
    },
    {
        id: "number",
        label: "Account Number",
        model: "number",
        type: "text",
        required: true,
        placeholder: "Enter bank account number",
    },
    {
        id: "type",
        label: "Account Type",
        model: "type",
        type: "select",
        required: true,
        options: [
            { value: "Savings", text: "Savings" },
            { value: "Checking", text: "Checking" },
            { value: "Payroll", text: "Payroll" },
            { value: "Others", text: "Others" },
        ],
    },
    {
        id: "status",
        label: "Status",
        model: "status",
        type: "select",
        required: true,
        options: [
            { value: "Active", text: "Active" },
            { value: "Inactive", text: "Inactive" },
        ],
    },
    {
        id: "balance",
        label: "Balance",
        model: "balance",
        type: "number",
        required: true,
        placeholder: "Enter current balance",
    },
    {
        id: "currency",
        label: "Currency",
        model: "currency",
        type: "select",
        required: true,
        options: [
            { value: "PHP", text: "PHP" },
            { value: "USD", text: "USD" },
            { value: "EUR", text: "EUR" },
        ],
    },
]);

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
    <AppLayout :title="`Create ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create
                    {{ singularizeAndFormat(modelName) }}
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
                    Create
                    {{ singularizeAndFormat(modelName) }}
                </template>
                <template #description>
                    <p>
                        Fill out the form below to create a new
                        {{ singularizeAndFormat(modelName).toLowerCase() }}.
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="text-red-500 font-semibold">*</span>
                        Fields marked with this symbol are required.
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
                        <!-- Display field-specific errors -->
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
