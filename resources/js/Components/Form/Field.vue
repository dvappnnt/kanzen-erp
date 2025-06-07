<script setup>
import FormLabel from "@/Components/Form/Label.vue";
import Dropzone from "@/Components/Form/Dropzone.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { useColors } from "@/Composables/useColors";

const { inputActiveBgColor } = useColors();

// Props Definition
defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: "text", // Default input type
    },
    modelValue: {
        type: [String, Number, Boolean, Array, File],
        default: null,
    },
    placeholder: {
        type: String,
        default: "",
    },
    required: {
        type: Boolean,
        default: false,
    },
    inputClasses: {
        type: String,
        default:
            "mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm",
    },
    options: {
        type: Array,
        default: () => [],
    },
});

// Define Emits
const emit = defineEmits(["update:modelValue"]);

// Handle Checkbox Update
const updateCheckbox = (value, checked) => {
    if (!Array.isArray(modelValue)) {
        emit("update:modelValue", checked ? [value] : []);
        return;
    }

    const newValue = checked
        ? [...modelValue, value]
        : modelValue.filter((v) => v !== value);

    emit("update:modelValue", newValue);
};

const showPassword = defineModel("showPassword", { default: false });

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div
        class="col-span-6 sm:col-span-6"
        :style="{ '--primary-color': inputActiveBgColor }"
    >
        <!-- Label -->
        <FormLabel
            v-if="type !== 'color'"
            :value="label"
            :required="required"
        />

        <!-- File Input -->
        <template v-if="type === 'file'">
            <Dropzone
                :id="id"
                :label="label"
                :modelValue="modelValue"
                @update:modelValue="emit('update:modelValue', $event)"
            />
        </template>

        <!-- Color Picker -->
        <template v-else-if="type === 'color'">
            <div class="flex items-center space-x-2">
                <input
                    :id="id"
                    type="color"
                    :value="modelValue"
                    @input="emit('update:modelValue', $event.target.value)"
                    class="h-8 w-8 p-0 border-gray-300 rounded-md shadow-sm focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)]"
                />
                <FormLabel :value="label" :required="required" />
            </div>
        </template>

        <!-- Checkboxes -->
        <template v-else-if="type === 'checkbox'">
            <div class="space-y-2">
                <div
                    v-for="option in options"
                    :key="option.value"
                    class="flex items-center"
                >
                    <input
                        type="checkbox"
                        :id="`${id}-${option.value}`"
                        :value="option.value"
                        :checked="
                            Array.isArray(modelValue)
                                ? modelValue.includes(option.value)
                                : false
                        "
                        @change="
                            updateCheckbox(option.value, $event.target.checked)
                        "
                        class="rounded border-gray-300 text-[var(--primary-color)] shadow-sm focus:ring-[var(--primary-color)]"
                    />
                    <label
                        :for="`${id}-${option.value}`"
                        class="ml-2 text-sm text-gray-700"
                    >
                        {{ option.text }}
                    </label>
                </div>
            </div>
        </template>

        <!-- Toggle Switch -->
        <template v-else-if="type === 'toggle'">
            <div class="flex items-center space-x-3">
                <button
                    type="button"
                    :class="[
                        modelValue
                            ? 'bg-[var(--primary-color)]'
                            : 'bg-gray-300',
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                    ]"
                    @click="emit('update:modelValue', !modelValue)"
                >
                    <span
                        :class="[
                            modelValue ? 'translate-x-6' : 'translate-x-1',
                            'inline-block h-4 w-4 transform rounded-full bg-white transition',
                        ]"
                    />
                </button>
                <span class="text-sm text-gray-700">{{ label }}</span>
            </div>
        </template>

        <!-- Select Element -->
        <template v-else-if="type === 'select'">
            <select
                :id="id"
                :value="modelValue"
                @change="emit('update:modelValue', $event.target.value)"
                :class="inputClasses"
            >
                <option value="" disabled>Select {{ label }}</option>
                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.text }}
                </option>
            </select>
        </template>

        <!-- Email Field -->
        <template v-else-if="type === 'email'">
            <input
                :id="id"
                type="email"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :class="inputClasses"
            />
        </template>

        <!-- Password Field -->
        <template v-else-if="type === 'password'">
            <div class="relative">
                <input
                    :id="id"
                    :type="showPassword ? 'text' : 'password'"
                    :value="modelValue"
                    @input="emit('update:modelValue', $event.target.value)"
                    :placeholder="placeholder"
                    :class="inputClasses"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 px-3 flex items-center focus:outline-none"
                    @click="togglePassword"
                >
                    <!-- Eye Open Icon -->
                    <span
                        v-if="!showPassword"
                        class="mdi mdi-eye-off w-5 h-5 text-gray-400"
                    ></span>

                    <!-- Eye Closed Icon -->
                    <span
                        v-else
                        class="mdi mdi-eye w-5 h-5 text-gray-400"
                    ></span>
                </button>
            </div>
        </template>

        <!-- Textarea Element -->
        <template v-else-if="type === 'textarea'">
            <textarea
                :id="id"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :class="inputClasses"
                rows="4"
            ></textarea>
        </template>

        <!-- Time Field -->
        <template v-else-if="type === 'time'">
            <input
                :id="id"
                type="time"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :class="inputClasses"
                step="60"
            />
        </template>

        <!-- Input Field (Generic Handler) -->
        <template v-else-if="type !== 'file'">
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :class="inputClasses"
                :step="
                    type === 'number' || type === 'price' ? 'any' : undefined
                "
                :min="type === 'number' || type === 'price' ? '0' : undefined"
            />
        </template>
    </div>
</template>
