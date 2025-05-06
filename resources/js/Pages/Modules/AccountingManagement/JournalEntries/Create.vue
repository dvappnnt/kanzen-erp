<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { parseInput } from "@/utils/parseInput";
import { useToast } from "vue-toastification";
import { singularizeAndFormat, formatNumber } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "journal-entries";
const page = usePage();
const isSubmitting = ref(false);
const toast = useToast();
const isLoadingAccounts = ref(false);
const companyAccounts = ref([]);

const formData = ref({
    company_id: page.props.auth?.user?.company_id,
    reference_number: '',
    reference_date: '',
    remarks: '',
    total_debit: 0.00,
    total_credit: 0.00,
    details: []
});

const errors = ref({});

const companies = computed(() =>
    Array.isArray(page.props.companies) ? page.props.companies : []
);

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
        id: "reference_number",
        label: "Reference Number",
        model: "reference_number",
        type: "text",
        required: true,
        placeholder: "Enter reference number (e.g., JRN-2025-00001)",
    },
    {
        id: "reference_date",
        label: "Reference Date",
        model: "reference_date",
        type: "date",
        required: true,
    },
    {
        id: "remarks",
        label: "Remarks",
        model: "remarks",
        type: "textarea",
        required: false,
        placeholder: "Enter any remarks (optional)",
    },
]);

const addNewRow = () => {
    formData.value.details.push({
        company_account_id: '',
        particulars: '',
        debit: 0.00,
        credit: 0.00,
        remarks: ''
    });
};

const removeRow = (index) => {
    formData.value.details.splice(index, 1);
    calculateTotals();
};

const calculateTotals = () => {
    formData.value.total_debit = formData.value.details.reduce((sum, detail) => sum + (parseFloat(detail.debit) || 0), 0);
    formData.value.total_credit = formData.value.details.reduce((sum, detail) => sum + (parseFloat(detail.credit) || 0), 0);
};

watch(() => formData.value.details, calculateTotals, { deep: true });

const submitForm = async () => {
    try {
        isSubmitting.value = true;

        if (formData.value.total_debit !== formData.value.total_credit) {
            toast.error("Total debit must equal total credit!");
            return;
        }

        if (formData.value.details.length === 0) {
            toast.error("Please add at least one journal entry detail!");
            return;
        }

        const response = await axios.post(`/api/${modelName}`, formData.value);
        toast.success("Journal entry created successfully!");
        router.get(`/${modelName}/${response.data.modelData.id}`);
    } catch (error) {
        toast.error(error.response?.data?.message || "Something went wrong!");
        if (error.response?.data?.errors) {
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
                    Create {{ singularizeAndFormat(modelName) }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-7xl mx-auto">
            <FormSetup
                :form-classes="'md:grid md:grid-cols-1 md:gap-2'"
                :col-span="'md:col-span-1'"
                @submitted="submitForm"
            >
                <template #title>
                    Create {{ singularizeAndFormat(modelName) }}
                </template>
                <template #description>
                    <p>Fill out the form below to create a new {{ singularizeAndFormat(modelName).toLowerCase() }}.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="text-red-500 font-semibold">*</span>
                        Fields marked with this symbol are required.
                    </p>
                </template>

                <!-- Form Fields -->
                <template #form>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <InputError :message="errors[field.model]" />
                        </div>
                    </div>

                    <!-- Journal Entry Details Table -->
                    <div class="mt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Journal Entry Details</h3>
                            <button
                                type="button"
                                @click="addNewRow"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Add Row
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                        <th class="px-3 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particulars</th>
                                        <th class="px-3 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                        <th class="px-3 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                        <th class="px-3 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        <th class="px-3 py-2 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(detail, index) in formData.details" :key="index" class="hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            <Autocomplete
                                                v-model="detail.company_account_id"
                                                :items="companyAccounts"
                                                item-text="name"
                                                item-value="id"
                                                placeholder="Select account"
                                                :loading="isLoadingAccounts"
                                                search-url="/api/autocomplete/company-accounts"
                                                @selected="item => detail.company_account = item"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                v-model="detail.particulars"
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Enter particulars"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="number"
                                                v-model="detail.debit"
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                step="0.01"
                                                min="0"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="number"
                                                v-model="detail.credit"
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                step="0.01"
                                                min="0"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                v-model="detail.remarks"
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Enter remarks"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button
                                                @click="removeRow(index)"
                                                type="button"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="formData.details.length === 0">
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                                            No details added yet. Click "Add Row" to add journal entry details.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td class="px-3 py-2 text-right font-medium" colspan="2">Totals:</td>
                                        <td class="px-3 py-2 text-right font-medium">{{ formatNumber(formData.total_debit) }}</td>
                                        <td class="px-3 py-2 text-right font-medium">{{ formatNumber(formData.total_credit) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr v-if="formData.total_debit !== formData.total_credit" class="text-red-600">
                                        <td colspan="6" class="px-3 py-2 text-center">
                                            Warning: Total debit ({{ formatNumber(formData.total_debit) }}) does not equal total credit ({{ formatNumber(formData.total_credit) }})
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
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
