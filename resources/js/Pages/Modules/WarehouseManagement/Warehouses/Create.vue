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

const modelName = "warehouses";
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
        id: "name",
        label: `${singularizeAndFormat(modelName)} Name`,
        model: "name",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} name`,
        required: true,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "email",
        label: `${singularizeAndFormat(modelName)} Email Address`,
        model: "email",
        type: "email",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} email`,
        required: true,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "mobile",
        label: `${singularizeAndFormat(modelName)} Mobile`,
        model: "mobile",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} mobile`,
        required: true,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "landline",
        label: `${singularizeAndFormat(modelName)} Landline`,
        model: "landline",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} landline`,
        required: false,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "address",
        label: `${singularizeAndFormat(modelName)} Address`,
        model: "address",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} address`,
        required: true,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "description",
        label: `Tell us about your ${singularizeAndFormat(
            modelName
        ).toLowerCase()}`,
        model: "description",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} description`,
        required: false,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "website",
        label: `${singularizeAndFormat(modelName)} Website`,
        model: "website",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} website`,
        required: false,
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "avatar",
        label: `${singularizeAndFormat(modelName)} Avatar`,
        model: "avatar",
        type: "file",
        placeholder: `Upload ${singularizeAndFormat(
            modelName
        ).toLowerCase()} avatar`,
        required: false,
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
                    Create {{ singularizeAndFormat(modelName) }}
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
                    Create {{ singularizeAndFormat(modelName) }}
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
