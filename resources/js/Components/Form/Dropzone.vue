<script setup>
import { ref, onMounted } from "vue";
import Dropzone from "dropzone";

// Props
const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: [Array, String, File, Object], // Support both Array and String
        default: () => [], // Default to an empty array
    },
    acceptedFiles: {
        type: String,
        default: "*/*", // Accept all files by default
    },
    maxFiles: {
        type: Number,
        default: 10,
    },
    maxFilesize: {
        type: Number,
        default: 10, // Default max file size in MB
    },
});

// Emits
const emit = defineEmits(["update:modelValue"]);

// Reference to Dropzone container
const dropzoneRef = ref(null);

onMounted(() => {
    const dropzone = new Dropzone(dropzoneRef.value, {
        url: "/", // Update with your backend endpoint
        autoProcessQueue: false, // Prevent automatic upload
        addRemoveLinks: false,
        acceptedFiles: props.acceptedFiles,
        maxFiles: props.maxFiles,
        maxFilesize: props.maxFilesize,
        previewTemplate: `
            <div class="dz-preview dz-file-preview" style="margin-bottom: 20px;">
                <div class="dz-image" style="display: flex; justify-content: center; align-items: center;">
                    <img data-dz-thumbnail style="max-width: 100px; max-height: 100px; border-radius: 8px;" />
                    <div class="dz-file-icon" style="display: none;">
                        <i class="fas fa-file" style="font-size: 48px; color: #666;"></i>
                    </div>
                </div>
                <div class="dz-details" style="text-align: center; margin-top: 10px;">
                    <div class="dz-size" style="font-size: 12px; color: #333; margin-bottom: 5px;"><span data-dz-size></span></div>
                    <div class="dz-filename" style="font-size: 14px; color: #555;"><span data-dz-name></span></div>
                    <div class="dz-type" style="font-size: 12px; color: #666; margin-top: 5px;"><span data-dz-type></span></div>
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <button class="dz-remove" data-dz-remove style="background-color: #f44336; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;">Remove file</button>
                </div>
            </div>
        `,

        init() {
            // Handle added file
            this.on("addedfile", (file) => {
                // Show file icon for non-image files
                if (!file.type.startsWith('image/')) {
                    const previewElement = file.previewElement;
                    if (previewElement) {
                        const imageElement = previewElement.querySelector('.dz-image img');
                        const iconElement = previewElement.querySelector('.dz-file-icon');
                        if (imageElement) imageElement.style.display = 'none';
                        if (iconElement) iconElement.style.display = 'block';
                    }
                }
                // Emit the raw File object
                emit("update:modelValue", file);
            });

            // Handle removed file
            this.on("removedfile", (file) => {
                if (Array.isArray(props.modelValue)) {
                    const updatedFiles = props.modelValue.filter(
                        (f) => f.name !== file.name
                    );
                    emit("update:modelValue", updatedFiles);
                } else {
                    emit("update:modelValue", null);
                }
            });

            // Handle initial preview for single file as string
            if (typeof props.modelValue === "string") {
                const mockFile = { name: props.modelValue, size: 12345 };
                this.emit("addedfile", mockFile);
                this.emit("complete", mockFile);
                this.options.thumbnail.call(this, mockFile, props.modelValue);
            }
        },
    });
});
</script>

<template>
    <div>
        <div
            ref="dropzoneRef"
            class="dropzone mt-2 border-dashed border-2 border-gray-300 rounded-lg p-4"
            :id="id"
        >
            <p v-if="!modelValue" class="text-sm text-gray-500 text-center">
                Drag and drop any files here, or click to upload
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Dropzone Container */
.dropzone {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    background-color: #fafafa;
    text-align: center;
    padding: 20px;
    border: 2px dashed #d3d3d3;
    border-radius: 8px;
}

/* Dropzone Message */
.dropzone .dz-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #666;
}

/* Dropzone Preview */
.dz-preview {
    display: flex;
    flex-direction: column;
    justify-content: center !important;
    align-items: center !important;
    width: auto !important;
    margin: 15px auto !important;
}

/* Dropzone Image */
.dz-image {
    display: flex;
    justify-content: center !important;
    align-items: center !important;
    margin: 0 auto;
    max-width: 150px;
    max-height: 150px;
    overflow: hidden;
    border-radius: 8px;
}

/* Dropzone Details */
.dz-details {
    text-align: center !important;
    margin-top: 10px;
}

/* File Name and Size */
.dz-details .dz-size {
    font-size: 12px;
    color: #333;
}

.dz-details .dz-filename {
    font-size: 14px;
    color: #555;
}

/* Remove Button */
.dz-remove {
    margin-top: 10px;
    padding: 5px 10px;
    background-color: #f44336;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
}

.dz-remove:hover {
    background-color: #d32f2f;
}

/* File Icon Styles */
.dz-file-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100px;
    height: 100px;
    background-color: #f5f5f5;
    border-radius: 8px;
    margin: 0 auto;
}

.dz-file-icon i {
    font-size: 48px;
    color: #666;
}
</style>
