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
const isExporting = ref(false);
const isImporting = ref(false);
const page = usePage();
const toast = useToast();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([]);

const formData = ref({
    frequency: "daily",
    isEnabled: false,
});
const errors = ref({});

const fields = ref([
    {
        id: "frequency",
        label: "Backup Frequency",
        model: "frequency",
        type: "select",
        required: true,
        placeholder: "Select backup frequency",
        options: [
            { value: "hourly", text: "Hourly" },
            { value: "daily", text: "Daily" },
            { value: "weekly", text: "Weekly" },
            { value: "yearly", text: "Yearly" },
        ],
        class: "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    {
        id: "isEnabled",
        label: "Enable Automatic Backups",
        model: "isEnabled",
        type: "checkbox",
        required: false,
        class: "mt-1 h-4 w-4 rounded border-gray-300 text-[var(--primary-color)] focus:ring-[var(--primary-color)]",
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
            `/api/${modelName}/schedule`,
            formDataObj,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );
        toast.success("Backup settings updated successfully!");
    } catch (error) {
        toast.error("Something went wrong!");
        if (error.response && error.response.data.errors) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isSubmitting.value = false;
    }
};

const exportDatabase = async () => {
    try {
        isExporting.value = true;
        const response = await axios.get(`/api/${modelName}/export-database`, {
            responseType: "blob",
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "database_backup.sql");
        document.body.appendChild(link);
        link.click();
        link.remove();

        toast.success("Database exported successfully!");
    } catch (error) {
        toast.error("Failed to export database!");
        console.error("Error exporting database:", error);
    } finally {
        isExporting.value = false;
    }
};

const importDatabase = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
        isImporting.value = true;
        const formData = new FormData();
        formData.append("database_file", file);

        const response = await axios.post(
            `/api/${modelName}/import-database`,
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        toast.success("Database imported successfully!");
    } catch (error) {
        toast.error("Failed to import database!");
        console.error("Error importing database:", error);
    } finally {
        isImporting.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Database Settings`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Database Settings
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
                        <template #title>Database Backup Settings</template>
                        <template #description>
                            <p>
                                Configure automatic database backup settings and
                                manage manual backups.
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
                                <InputError :message="errors[field.model]" />
                            </div>

                            <!-- Manual Database Management -->
                            <div class="mt-8 border-t pt-6">
                                <h3
                                    class="text-lg font-medium text-gray-900 mb-4"
                                >
                                    Manual Database Management
                                </h3>
                                <div class="flex space-x-4">
                                    <button
                                        type="button"
                                        @click="exportDatabase"
                                        :disabled="isExporting"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                                        :class="{
                                            'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                                        }"
                                        :style="{
                                            '--primary-color': buttonPrimaryBgColor,
                                        }"
                                    >
                                        <span
                                            v-if="isExporting"
                                            class="animate-spin mr-2"
                                        >
                                            <svg
                                                class="w-4 h-4 text-white"
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
                                        {{
                                            isExporting
                                                ? "Exporting..."
                                                : "Export Database"
                                        }}
                                    </button>

                                    <label
                                        :class="{
                                            'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                                        }"
                                        :style="{
                                            '--primary-color': buttonPrimaryBgColor,
                                        }"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest cursor-pointer focus:outline-none focus:ring disabled:opacity-25 transition"
                                    >
                                        <input
                                            type="file"
                                            class="hidden"
                                            @change="importDatabase"
                                            :disabled="isImporting"
                                        />
                                        <span
                                            v-if="isImporting"
                                            class="animate-spin mr-2"
                                        >
                                            <svg
                                                class="w-4 h-4 text-white"
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
                                        {{
                                            isImporting
                                                ? "Importing..."
                                                : "Import Database"
                                        }}
                                    </label>
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
