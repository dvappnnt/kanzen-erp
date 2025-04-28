<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { parseInput } from "@/utils/parseInput";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const modelName = "expenses";
const isSubmitting = ref(false);
const page = usePage();
const toast = useToast();

const formData = ref({
    company_id: page.props.auth?.user?.company_id
});
const errors = ref({}); // Object to hold error messages

const companies = computed(() =>
    Array.isArray(page.props.companies) ? page.props.companies : []
);

const categories = computed(() =>
    Array.isArray(page.props.categories) ? page.props.categories : []
);

const suppliers = computed(() =>
    Array.isArray(page.props.suppliers) ? page.props.suppliers : []
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
        id: "supplier_id",
        label: "Supplier",
        model: "supplier_id",
        type: "select",
        required: false,
        options: suppliers.value.map((supplier) => ({
            value: supplier.id,
            text: supplier.name.charAt(0).toUpperCase() + supplier.name.slice(1),
        })),
    },
    {
        id: "category_id",
        label: "Expense Category",
        model: "category_id",
        type: "select",
        required: true,
        options: categories.value.map((category) => ({
            value: category.id,
            text: category.name.charAt(0).toUpperCase() + category.name.slice(1),
        })),
    },
    {
        id: "payment_method",
        label: "Payment Method",
        model: "payment_method",
        type: "select",
        required: true,
        options: [
            { value: "Cash", text: "Cash" },
            { value: "Bank Transfer", text: "Bank Transfer" },
            { value: "Cheque", text: "Cheque" },
            { value: "Online Payment", text: "Online Payment" },
            { value: "Others", text: "Others" },
        ],
    },
    {
        id: "amount",
        label: "Amount",
        model: "amount",
        type: "number",
        required: true,
        placeholder: "Enter amount",
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
    {
        id: "expense_date",
        label: "Expense Date",
        model: "expense_date",
        type: "date",
        required: true,
    },
    {
        id: "description",
        label: "Description",
        model: "description",
        type: "textarea",
        required: false,
        placeholder: "Enter any notes about this expense",
    },
]);

onMounted(() => {
    // Merge the existing model data with form data while preserving the default company_id
    formData.value = { 
        ...formData.value,
        ...page.props.modelData 
    };
});

const submitForm = async () => {
    try {
        isSubmitting.value = true;

        const formDataObj = parseInput(fields.value, formData.value);
        formDataObj.append("_method", "PUT");
        const response = await axios.post(
            `/api/${modelName}/${formData.value.id}`,
            formDataObj,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );
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
    <AppLayout :title="`Edit ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit
                    {{
                        singularizeAndFormat(modelName)
                            .charAt(0)
                            .toUpperCase() +
                        singularizeAndFormat(modelName).slice(1)
                    }}
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
                    Edit {{ singularizeAndFormat(modelName) }}
                </template>
                <template #description>
                    <p>
                        Modify the form below to update the
                        {{ singularizeAndFormat(modelName).toLowerCase() }}
                        details.
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
