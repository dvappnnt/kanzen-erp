<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import axios from "@/axios";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const page = usePage();
const modelName = "employee-overtimes";
const isSubmitting = ref(false);
const toast = useToast();

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const formData = ref({});
const errors = ref({});

const employees = computed(() =>
    Array.isArray(page.props.employees) ? page.props.employees : []
);

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const fields = computed(() => [
    {
        id: "employee_id",
        label: "Employee",
        model: "employee_id",
        type: "select",
        required: true,
        options: employees.value.map((employee) => ({
            value: employee.id,
            text:
                employee.lastname.charAt(0).toUpperCase() +
                employee.lastname.slice(1) +
                ", " +
                employee.firstname.charAt(0).toUpperCase() +
                employee.firstname.slice(1),
        })),
    },
    {
        id: "date",
        label: "Date",
        model: "date",
        type: "date",
        placeholder: "Select date",
        required: true,
    },
    {
        id: "start_time",
        label: "Start Time",
        model: "start_time",
        type: "time",
        placeholder: "Select start time",
        required: true,
    },
    {
        id: "end_time",
        label: "End Time",
        model: "end_time",
        type: "time",
        placeholder: "Select end time",
        required: true,
    },
    {
        id: "reason",
        label: "Reason",
        model: "reason",
        type: "textarea",
        placeholder: "Enter reason",
        required: true,
    },
]);

// Initialize formData on Mount
onMounted(() => {
    formData.value = {
        ...page.props.modelData,
    };
});

// Submit Form Function
const submitForm = async () => {
    try {
        isSubmitting.value = true;

        // Axios PUT Request for Update
        const { data } = await axios.put(
            `/api/${modelName}/${formData.value.id}`,
            formData.value
        );
        toast.success("Submitted successfully!");
        const modelDataId = data.modelData.id;
        router.get(`/${modelName}/${modelDataId}`);
    } catch (error) {
        toast.error("Something went wrong!");
        if (error.response && error.response.data.errors) {
            errors.value = error.response.data.errors; // Handle validation errors
        }
    } finally {
        isSubmitting.value = false; // Reset Submission
    }
};
</script>

<template>
    <AppLayout :title="`Edit ${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit {{ singularizeAndFormat(modelName) }} Details
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
                <template #title
                    >Edit
                    {{ singularizeAndFormat(modelName) }} Details</template
                >
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
                        <span v-if="!isSubmitting">Update</span>
                        <span v-else>Updating...</span>
                    </button>
                </template>
            </FormSetup>
        </div>
    </AppLayout>
</template>
