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

const modelName = "categories";
const page = usePage();
const isSubmitting = ref(false);
const toast = useToast();

const formData = ref({});
const errors = ref({}); // Object to hold error messages

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
        id: "from_date",
        label: "From Date",
        model: "from_date",
        type: "date",
        placeholder: `Enter from date`,
        required: true,
    },
    {
        id: "to_date",
        label: "To Date",
        model: "to_date",
        type: "date",
        placeholder: `Enter to date`,
        required: true,
    },
]);

const submitForm = async () => {
    try {
        isSubmitting.value = true;
        errors.value = {}; // Clear previous errors

        // Validate required fields
        let hasErrors = false;
        fields.value.forEach(field => {
            if (field.required && !formData.value[field.model]) {
                errors.value[field.model] = `${field.label} is required`;
                hasErrors = true;
            }
        });

        if (hasErrors) {
            toast.error("Please fill in all required fields");
            return;
        }

        const formDataObj = parseInput(fields.value, formData.value);
        const response = await axios.post(`/api/${modelName}/export`, formDataObj, {
            responseType: 'blob', // Important for file downloads
            headers: {
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
        });

        // Create a blob from the response data
        const blob = new Blob([response.data], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        
        // Create a URL for the blob
        const url = window.URL.createObjectURL(blob);
        
        // Create a temporary link element
        const link = document.createElement('a');
        link.href = url;
        
        // Get filename from the response headers or use a default
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'purchase_orders_export.xlsx';
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1].replace(/['"]/g, '');
            }
        }
        
        link.setAttribute('download', filename);
        
        // Append link to body, click it, and remove it
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up the URL
        window.URL.revokeObjectURL(url);
        
        toast.success("Export completed successfully!");
        router.get(`/${modelName}`);
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
    <AppLayout :title="`Export ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Export
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
                    Export
                    {{ singularizeAndFormat(modelName) }}
                </template>
                <template #description>
                    <p>
                        Fill out the form below to export {{ singularizeAndFormat(modelName).toLowerCase() }}.
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
