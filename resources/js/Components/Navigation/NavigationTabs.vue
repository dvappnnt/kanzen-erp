<template>
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-4 px-4" aria-label="Tabs">
                <Link
                    v-for="tab in filteredTabs"
                    :key="tab.text"
                    :href="tab.url"
                    :class="[
                        isCurrentRoute(tab.url)
                            ? 'active-tab'
                            : 'hover:border-gray-300 hover:text-gray-700',
                        'whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm',
                    ]"
                    :style="getTabStyles(tab.url)"
                >
                    {{ tab.text }}
                </Link>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const hasPermission = (permission) => {
    return page.props.permissions?.includes(permission);
};

const filteredTabs = computed(() => {
    return props.tabs.filter(tab => {
        if (!tab.permission) return true;
        return hasPermission(tab.permission);
    });
});

const isCurrentRoute = (url) => {
    const currentPath = window.location.pathname;
    return currentPath === url;
};

const getTabStyles = (url) => {
    if (isCurrentRoute(url)) {
        return {
            borderColor: buttonPrimaryBgColor.value,
            color: buttonPrimaryBgColor.value,
        };
    }
    return {
        borderColor: 'transparent',
        color: '#6B7280',
    };
};
</script>

<style scoped>
.active-tab {
    border-bottom-width: 2px;
}
</style> 