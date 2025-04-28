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
    // PUSHER Fields
    {
        id: "pusher_app_id",
        label: "Pusher App ID",
        model: "pusher_app_id",
        type: "text",
        placeholder: "Enter Pusher App ID",
        required: true,
    },
    {
        id: "pusher_app_key",
        label: "Pusher App Key",
        model: "pusher_app_key",
        type: "text",
        placeholder: "Enter Pusher App Key",
        required: true,
    },
    {
        id: "pusher_app_secret",
        label: "Pusher App Secret",
        model: "pusher_app_secret",
        type: "text",
        placeholder: "Enter Pusher App Secret",
        required: true,
    },
    {
        id: "pusher_app_cluster",
        label: "Pusher App Cluster",
        model: "pusher_app_cluster",
        type: "text",
        placeholder: "Enter Pusher App Cluster",
        required: true,
    },

    // GOOGLE Fields
    {
        id: "google_client_id",
        label: "Google Client ID",
        model: "google_client_id",
        type: "text",
        placeholder: "Enter Google Client ID",
        required: true,
    },
    {
        id: "google_client_secret",
        label: "Google Client Secret",
        model: "google_client_secret",
        type: "text",
        placeholder: "Enter Google Client Secret",
        required: true,
    },
    {
        id: "google_redirect_uri",
        label: "Google Redirect URI",
        model: "google_redirect_uri",
        type: "text",
        placeholder: "Enter Google Redirect URI",
        required: true,
    },

    // STRIPE Fields
    {
        id: "stripe_key",
        label: "Stripe Key",
        model: "stripe_key",
        type: "text",
        placeholder: "Enter Stripe Key",
        required: true,
    },
    {
        id: "stripe_secret",
        label: "Stripe Secret",
        model: "stripe_secret",
        type: "text",
        placeholder: "Enter Stripe Secret",
        required: true,
    },
    {
        id: "stripe_webhook_secret",
        label: "Stripe Webhook Secret",
        model: "stripe_webhook_secret",
        type: "text",
        placeholder: "Enter Stripe Webhook Secret",
        required: true,
    },

    // OPENAI Fields
    {
        id: "openai_api_url",
        label: "OpenAI API URL",
        model: "openai_api_url",
        type: "text",
        placeholder: "Enter OpenAI API URL",
        required: true,
    },
    {
        id: "openai_api_key",
        label: "OpenAI API Key",
        model: "openai_api_key",
        type: "text",
        placeholder: "Enter OpenAI API Key",
        required: true,
    },

    // CLAUDE (Hypothetical Example)
    {
        id: "claude_api_url",
        label: "Claude API URL",
        model: "claude_api_url",
        type: "text",
        placeholder: "Enter Claude API URL",
        required: true,
    },
    {
        id: "claude_api_key",
        label: "Claude API Key",
        model: "claude_api_key",
        type: "text",
        placeholder: "Enter Claude API Key",
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
            `/api/${modelName}/environment/update`,
            formDataObj,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );
        toast.success("Submitted successfully!");
        router.get(`/app/settings/environment`);
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
    <AppLayout :title="`Environment Settings`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Environment Settings
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
