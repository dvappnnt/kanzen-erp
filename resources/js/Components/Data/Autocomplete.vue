<template>
    <div :style="{ '--primary-color': primaryColor }" class="relative">
        <input
            type="text"
            v-model="searchTerm"
            @input="fetchAutocompleteResults"
            :placeholder="placeholder"
            class="border border-gray-300 rounded-md w-full py-2 px-4 text-gray-900 placeholder-gray-500 bg-white focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-1 ring-opacity-50 transition-all duration-200"
            autocomplete="off"
        />

        <ul
            v-if="showResults && autocompleteResults.length"
            class="absolute bg-white border mt-1 rounded-md w-full"
        >
            <li
                v-for="selectedModelData in autocompleteResults"
                :key="selectedModelData.id"
                @click="selectModelData(selectedModelData)"
                class="p-2 hover:bg-gray-100 cursor-pointer"
            >
                {{ selectedModelData.name ?? selectedModelData.title }}
            </li>
        </ul>

        <div
            v-if="showResults && !autocompleteResults.length && !isLoading"
            class="absolute bg-white border mt-1 rounded-md w-full p-2 text-gray-500 text-center"
        >
            No results found
        </div>

        <div v-if="isLoading" class="absolute top-0 right-0 mt-2 mr-4">
            <svg
                class="animate-spin h-5 w-5 text-gray-500"
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
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import axios from "@/axios";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const primaryColor = computed(() => page.props.appSettings?.input_active_bg_color || "#1F2937");

// Define Props
const props = defineProps({
    searchUrl: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: "Search...",
    },
    modelName: {
        type: String,
        required: true,
    },
    mapCustomButtons: {
        type: Function, // Accept a function for button mapping
        required: true,
    },
});

// Define Emits
const emit = defineEmits(["select"]);

const searchTerm = ref("");
const autocompleteResults = ref([]);
const showResults = ref(false);
const isLoading = ref(false);

// Fetch Autocomplete Results
const fetchAutocompleteResults = async () => {
    if (searchTerm.value.trim().length > 0) {
        isLoading.value = true;
        try {
            const response = await axios.get(
                `${props.searchUrl}?search=${searchTerm.value}`
            );
            autocompleteResults.value = response.data.data || [];
            showResults.value = true;
        } catch (error) {
            console.error("Error fetching search results:", error);
        } finally {
            isLoading.value = false;
        }
    } else {
        autocompleteResults.value = [];
        showResults.value = false;
    }
};

// Select Model Data
const selectModelData = async (selectedModelData) => {
    if (!selectedModelData?.id) {
        console.error("Invalid selection: Missing ID");
        return;
    }

    try {
        isLoading.value = true;

        // Fetch detailed model data
        const response = await axios.get(
            `/api/${props.modelName}/${selectedModelData.id}`
        );

        const fetchedData = response.data;

        // Use the mapping function from props
        const responseData = {
            data: [props.mapCustomButtons(fetchedData)],
            links: [],
        };

        emit("select", responseData); // Emit Data
        searchTerm.value = selectedModelData.name;
        showResults.value = false;
    } catch (error) {
        console.error("Error fetching selected model data:", error);
        alert("Failed to load the selected data.");
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
/* Custom styles if needed */
</style>
