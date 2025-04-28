<template>
    <nav class="w-full md:w-64 bg-white p-4 rounded-lg shadow md:static fixed bottom-0 left-0 md:bottom-auto md:left-auto z-50 md:z-auto">
        <ul class="space-y-2 md:space-y-2 flex md:flex-col justify-around md:justify-start">
            <li
                v-for="(link, index) in links"
                :key="link.label"
                :class="[
                    'flex items-center rounded-lg transition duration-150 ease-in-out',
                    isActive(link.url) ? 'text-white' : 'hover:text-white'
                ]"
                :style="
                    isActive(link.url)
                        ? { backgroundColor: primaryColor, color: activeTextColor }
                        : hoverStyle[index]
                "
                @mouseover="applyHoverStyle(index)"
                @mouseleave="removeHoverStyle(index)"
            >
                <!-- Entire li is clickable -->
                <Link
                    :href="link.url"
                    class="flex items-center flex-1 space-x-3 py-2 px-3 rounded-lg"
                >
                    <!-- Optional Icon -->
                    <span 
                        v-if="link.icon" 
                        :class="'mdi ' + link.icon + ' text-xl'"
                        :style="isActive(link.url) ? { color: activeTextColor } : {}"
                    ></span>
                    <!-- Link Label -->
                    <span class="hidden md:inline">{{ link.label }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import { defineProps, computed, reactive } from "vue";
import { usePage } from "@inertiajs/vue3";

// Props
const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
});

// Access appSettings from Inertia.js page props
const { appSettings } = usePage().props;

const stripQuotes = (value) => {
    return value && value.startsWith('"') && value.endsWith('"')
        ? value.slice(1, -1)
        : value;
};

// Extract colors dynamically
const primaryColor = computed(() => appSettings?.sidebar_active_bg_color || "#4350b1");
const activeTextColor = computed(() => {
    return appSettings?.sidebar_active_text_color
        ? stripQuotes(appSettings.sidebar_active_text_color)
        : "#000000";
});

// Hover style for each link
const hoverStyle = reactive(props.links.map(() => ({ backgroundColor: "transparent", color: "inherit" })));

/**
 * Checks if the current URL matches the given link's URL.
 */
const isActive = (url) => {
    const page = usePage();
    return page.url === url;
};

/**
 * Apply hover style dynamically on mouseover, but only for inactive links.
 */
const applyHoverStyle = (index) => {
    if (!isActive(props.links[index].url)) {
        hoverStyle[index] = { 
            backgroundColor: primaryColor.value, 
            color: activeTextColor.value 
        };
    }
};

/**
 * Remove hover style dynamically on mouseleave, but only for inactive links.
 */
const removeHoverStyle = (index) => {
    if (!isActive(props.links[index].url)) {
        hoverStyle[index] = { backgroundColor: "transparent", color: "inherit" };
    }
};
</script>
