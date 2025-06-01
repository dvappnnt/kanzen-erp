<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import FormSetup from "@/Components/Form/Setup.vue";
import FormField from "@/Components/Form/Field.vue";
import Dropzone from "@/Components/Form/Dropzone.vue";
import InputError from "@/Components/InputError.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "vue-toastification";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";

const modelName = "products";
const isSubmitting = ref(false);
const toast = useToast();
const formData = ref({});
const errors = ref({});
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
        id: "file_upload",
        label: `${singularizeAndFormat(modelName)} File Upload`,
        model: "file_upload",
        type: "file",
        required: true,
    },
]);

const submitForm = async () => {
    try {
        isSubmitting.value = true;
        errors.value = {};
        // Validate file
        if (!formData.value.file_upload) {
            errors.value.file_upload = "File is required";
            toast.error("Please select a file to import");
            return;
        }
        const data = new FormData();
        data.append("file", formData.value.file_upload);
        const response = await axios.post(`/api/${modelName}/import`, data, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        toast.success("Import completed successfully!");
        router.get(`/${modelName}`);
    } catch (error) {
        toast.error("Something went wrong!");
        if (error.response && error.response.data.errors) {
            errors.value = error.response.data.errors;
        } else if (error.response && error.response.data.message) {
            errors.value.file_upload = error.response.data.message;
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Import ${singularizeAndFormat(modelName)}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Import {{ singularizeAndFormat(modelName) }}
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
                    Import {{ singularizeAndFormat(modelName) }}
                </template>
                <template #description>
                    <p>
                        Upload a file to import {{ singularizeAndFormat(modelName).toLowerCase() }}.
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="text-red-500 font-semibold">*</span>
                        File is required.
                    </p>
                </template>
                <template #form>
                    <Dropzone
                        id="file_upload"
                        label="File Upload"
                        v-model="formData.file_upload"
                        :accepted-files="'.xlsx,.xls,.csv'"
                        :max-files="1"
                        :max-filesize="10"
                    />
                    <InputError :message="errors.file_upload" />
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
                        <span v-if="!isSubmitting">Import</span>
                        <span v-else>Importing...</span>
                    </button>
                </template>
            </FormSetup>
        </div>
    </AppLayout>
</template>
