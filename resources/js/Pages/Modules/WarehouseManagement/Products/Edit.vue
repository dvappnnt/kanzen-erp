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

const modelName = "products";
const isSubmitting = ref(false);
const page = usePage();
const toast = useToast();

const formData = ref({});
const errors = ref({}); // Object to hold error messages

const categories = computed(() =>
    Array.isArray(page.props.categories) ? page.props.categories : []
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
        id: "name",
        label: `${singularizeAndFormat(modelName)} Name`,
        model: "name",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} name`,
        required: true,
    },
    {
        id: "category_id",
        label: "Category",
        model: "category_id",
        type: "select",
        required: true,
        options: categories.value.map((category) => ({
            value: category.id,
            text:
                category.name.charAt(0).toUpperCase() + category.name.slice(1),
        })),
    },
    {
        id: "description",
        label: `${singularizeAndFormat(modelName)} Description`,
        model: "description",
        type: "text",
        placeholder: `Enter ${singularizeAndFormat(
            modelName
        ).toLowerCase()} description`,
        required: false,
    },
    {
        id: "has_variation",
        label: `${singularizeAndFormat(modelName)} Has Variation`,
        model: "has_variation",
        type: "toggle",
        placeholder: `Enter ${singularizeAndFormat(modelName).toLowerCase()}`,
        required: true,
        default: true, // <-- Add default true here
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

onMounted(() => {
    formData.value = { ...page.props.modelData };
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
