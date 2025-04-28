<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Response',
    },
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String, // success, error, info
        default: 'info',
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};

// Icon configuration based on type
const iconConfig = {
    success: {
        color: 'text-green-600',
        bgColor: 'bg-green-100',
        path: 'M4.5 12.75l6 6 9-13.5',
    },
    error: {
        color: 'text-red-600',
        bgColor: 'bg-red-100',
        path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    },
    info: {
        color: 'text-blue-600',
        bgColor: 'bg-blue-100',
        path: 'M12 9v3m0 3h.01M12 5.75a.75.75 0 110 1.5.75.75 0 010-1.5z',
    },
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <!-- Icon based on type -->
                <div
                    class="mx-auto shrink-0 flex items-center justify-center size-12 rounded-full sm:mx-0 sm:size-10"
                    :class="iconConfig[type]?.bgColor"
                >
                    <svg
                        class="size-6"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        :class="iconConfig[type]?.color"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :d="iconConfig[type]?.path"
                        />
                    </svg>
                </div>

                <!-- Title and Content -->
                <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ title }}
                    </h3>

                    <div class="mt-4 text-sm text-gray-600">
                        {{ message }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 text-end">
            <button
                @click="close"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            >
                Close
            </button>
        </div>
    </Modal>
</template>
