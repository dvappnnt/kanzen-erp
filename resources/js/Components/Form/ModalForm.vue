<script setup>
import { ref, computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import FormField from "@/Components/Form/Field.vue";
import InputError from "@/Components/InputError.vue";
import { useToast } from "vue-toastification";
import { useColors } from "@/Composables/useColors";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    fields: {
        type: Array,
        required: true,
    },
    modelData: {
        type: Object,
        default: () => ({}),
    },
    submitUrl: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: "post",
    },
});

const emit = defineEmits(["close", "updated"]);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();
const toast = useToast();
const isSubmitting = ref(false);
const errors = ref({});
const formData = ref({});

// Initialize form data when modal opens
const initializeFormData = () => {
    formData.value = { ...props.modelData };
};

// Watch for show changes to initialize form data
watch(() => props.show, (newValue) => {
    if (newValue) {
        initializeFormData();
    }
});

const submitForm = async () => {
    try {
        isSubmitting.value = true;
        let response;

        if (props.method.toLowerCase() === "post") {
            response = await axios.post(props.submitUrl, formData.value);
        } else if (props.method.toLowerCase() === "put") {
            response = await axios.put(props.submitUrl, formData.value);
        }

        toast.success("Submitted successfully!");
        emit("updated", response.data.modelData);
        emit("close");
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
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ title }}
            </h2>

            <div class="mt-6">
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
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

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                            @click="emit('close')"
                            :disabled="isSubmitting"
                        >
                            Cancel
                        </button>
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
                            <span v-else>Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template> 