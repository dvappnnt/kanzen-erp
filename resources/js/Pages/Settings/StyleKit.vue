<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import AppSettingsSidebar from "@/Components/Navigation/SidebarLinks/AppSettings.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { parseInput } from "@/utils/parseInput";
import { useToast } from "vue-toastification";
import { useColors } from "@/Composables/useColors";

const modelName = "app-settings";
const isSubmitting = ref(false);
const page = usePage();
const toast = useToast();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const formData = ref({});
const errors = ref({}); // Object to hold error messages

const headerActions = ref([]);

const fields = ref([
    // Navbar
    {
        id: "navbar_bg_color",
        label: "Navbar Background Color",
        model: "navbar_bg_color",
        type: "color",
        placeholder: "Pick a navbar background color",
        required: true,
    },
    {
        id: "navbar_text_color",
        label: "Navbar Text Color",
        model: "navbar_text_color",
        type: "color",
        placeholder: "Pick a navbar text color",
        required: true,
    },
    {
        id: "navbar_hover_bg_color",
        label: "Navbar Hover Background Color",
        model: "navbar_hover_bg_color",
        type: "color",
        placeholder: "Pick a navbar hover background color",
        required: true,
    },
    {
        id: "navbar_hover_text_color",
        label: "Navbar Hover Text Color",
        model: "navbar_hover_text_color",
        type: "color",
        placeholder: "Pick a navbar hover text color",
        required: true,
    },
    {
        id: "navbar_active_bg_color",
        label: "Navbar Active Background Color",
        model: "navbar_active_bg_color",
        type: "color",
        placeholder: "Pick a navbar active background color",
        required: true,
    },
    {
        id: "navbar_active_text_color",
        label: "Navbar Active Text Color",
        model: "navbar_active_text_color",
        type: "color",
        placeholder: "Pick a navbar active text color",
        required: true,
    },

    // Sidebar
    {
        id: "sidebar_bg_color",
        label: "Sidebar Background Color",
        model: "sidebar_bg_color",
        type: "color",
        placeholder: "Pick a sidebar background color",
        required: true,
    },
    {
        id: "sidebar_text_color",
        label: "Sidebar Text Color",
        model: "sidebar_text_color",
        type: "color",
        placeholder: "Pick a sidebar text color",
        required: true,
    },
    {
        id: "sidebar_hover_bg_color",
        label: "Sidebar Hover Background Color",
        model: "sidebar_hover_bg_color",
        type: "color",
        placeholder: "Pick a sidebar hover background color",
        required: true,
    },
    {
        id: "sidebar_hover_text_color",
        label: "Sidebar Hover Text Color",
        model: "sidebar_hover_text_color",
        type: "color",
        placeholder: "Pick a sidebar hover text color",
        required: true,
    },
    {
        id: "sidebar_active_bg_color",
        label: "Sidebar Active Background Color",
        model: "sidebar_active_bg_color",
        type: "color",
        placeholder: "Pick a sidebar active background color",
        required: true,
    },
    {
        id: "sidebar_active_text_color",
        label: "Sidebar Active Text Color",
        model: "sidebar_active_text_color",
        type: "color",
        placeholder: "Pick a sidebar active text color",
        required: true,
    },

    // Button
    {
        id: "button_primary_bg_color",
        label: "Button Primary Background Color",
        model: "button_primary_bg_color",
        type: "color",
        placeholder: "Pick a primary button background color",
        required: true,
    },
    {
        id: "button_primary_text_color",
        label: "Button Primary Text Color",
        model: "button_primary_text_color",
        type: "color",
        placeholder: "Pick a primary button text color",
        required: true,
    },

    // Input
    {
        id: "input_active_bg_color",
        label: "Input Active Background Color",
        model: "input_active_bg_color",
        type: "color",
        placeholder: "Pick an input active background color",
        required: true,
    },

    // Theme Colors
    {
        id: "primary_color",
        label: "Primary Theme Color",
        model: "primary_color",
        type: "color",
        placeholder: "Pick a primary theme color",
        required: true,
    },
    {
        id: "secondary_color",
        label: "Secondary Theme Color",
        model: "secondary_color",
        type: "color",
        placeholder: "Pick a secondary theme color",
        required: true,
    },
    {
        id: "success_color",
        label: "Success Color",
        model: "success_color",
        type: "color",
        placeholder: "Pick a success color",
        required: true,
    },
    {
        id: "danger_color",
        label: "Danger Color",
        model: "danger_color",
        type: "color",
        placeholder: "Pick a danger color",
        required: true,
    },
    {
        id: "warning_color",
        label: "Warning Color",
        model: "warning_color",
        type: "color",
        placeholder: "Pick a warning color",
        required: true,
    },
    {
        id: "info_color",
        label: "Info Color",
        model: "info_color",
        type: "color",
        placeholder: "Pick an info color",
        required: true,
    },
]);

onMounted(() => {
    formData.value = { ...page.props.settings };
});

const submitForm = async () => {
    try {
        isSubmitting.value = true;

        const formDataObj = parseInput(fields.value, formData.value);
        formDataObj.append("_method", "PUT");

        const response = await axios.post(
            `/api/${modelName}/style/update`,
            formDataObj,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );
        toast.success("Submitted successfully!");
        router.get(`/app/settings/style-kit`);
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
    <AppLayout :title="`Style Kit Settings`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Style Kit Settings
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-7xl">
            <div class="flex flex-col sm:flex-row">
                <!-- Sidebar -->
                <AppSettingsSidebar />

                <!-- Main Content -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex-1 sm:mt-0 sm:ml-6"
                >
                    <FormSetup
                        :form-classes="'md:grid md:grid-cols-1 md:gap-2'"
                        :col-span="'md:col-span-1'"
                        @submitted="submitForm"
                    >
                        <template #title> Edit App Settings </template>
                        <template #description>
                            <p>
                                Modify the form below to update the App
                                Settings.
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                <span class="text-red-500 font-semibold"
                                    >*</span
                                >
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
                                <span
                                    v-if="isSubmitting"
                                    class="animate-spin mr-2"
                                >
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
            </div>
        </div>
    </AppLayout>
</template>
