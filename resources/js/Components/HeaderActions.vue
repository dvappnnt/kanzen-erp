<template>
    <div class="flex space-x-2">
        <template v-for="(action, index) in actions" :key="index">
            <!-- Button for onClick actions -->
            <button
                v-if="action.onClick"
                :class="[
                    'cursor-pointer', 
                    action.class || 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded'
                ]"
                :style="getButtonStyle(action)"
                @click="action.onClick"
            >
                <span :class="['flex items-center', action.icon ? 'space-x-2' : '']">
                    <i 
                        v-if="action.icon" 
                        :class="action.icon" 
                        class="text-xl"
                        :style="'backgroundColor' in (typeof action.style === 'function' ? action.style() : (action.style || {})) ? { color: buttonPrimaryTextColor } : ''"
                    ></i>
                    <span>{{ action.text }}</span>
                </span>
            </button>

            <!-- Link for navigation actions -->
            <a
                v-else
            :href="action.inertia ? undefined : action.url"
            :class="[
                'cursor-pointer', 
                action.class || 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded'
            ]"
            :style="getButtonStyle(action)"
            @click.prevent="action.inertia ? handleInertia(action.url) : null"
        >
            <span :class="['flex items-center', action.icon ? 'space-x-2' : '']">
                <i 
                    v-if="action.icon" 
                    :class="action.icon" 
                    class="text-xl"
                    :style="'backgroundColor' in (typeof action.style === 'function' ? action.style() : (action.style || {})) ? { color: buttonPrimaryTextColor } : ''"
                ></i>
                <span>{{ action.text }}</span>
            </span>
        </a>
        </template>
    </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useColors } from '@/Composables/useColors';

// Props to receive the actions array
defineProps({
    actions: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const getButtonStyle = (action) => {
    if (action.style) {
        const style = typeof action.style === 'function' ? action.style() : action.style;
        // If there's a backgroundColor, replace it with button_primary_bg_color
        if ('backgroundColor' in style) {
            return {
                ...style,
                backgroundColor: buttonPrimaryBgColor.value,
                color: buttonPrimaryTextColor.value
            };
        }
        return style;
    }
    
    return action.style || '';
};

// Handle Inertia navigation
const handleInertia = (url) => {
    if (url) {
        router.get(url);
    }
};
</script>
