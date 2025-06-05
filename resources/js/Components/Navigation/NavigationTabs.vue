<template>
    <div class="mb-6">
        <!-- Mobile: Dropdown -->
        <div class="sm:hidden px-4">
            <select
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                :value="currentTabUrl"
                @change="onDropdownChange"
            >
                <option v-for="tab in filteredTabs" :key="tab.text" :value="tab.url">
                    {{ tab.text }}
                </option>
            </select>
        </div>

        <!-- Desktop: Scrollable Tabs with Arrows -->
        <div class="border-b border-gray-200 hidden sm:block relative group">
            <!-- Left Arrow -->
            <button
                @click="scrollTabs('left')"
                class="absolute left-0 top-0 bottom-0 z-10 bg-white px-2 hidden sm:flex items-center justify-center shadow group-hover:flex"
                v-show="canScrollLeft"
            >
                ‹
            </button>

            <!-- Tabs -->
            <div
                ref="tabsContainer"
                class="overflow-x-auto scrollbar-hide flex space-x-4 px-10 cursor-grab"
                @mousedown="startDrag"
                @mousemove="onDrag"
                @mouseup="stopDrag"
                @mouseleave="stopDrag"
                @scroll="checkScroll"
            >
                <Link
                    v-for="tab in filteredTabs"
                    :key="tab.text"
                    :href="tab.url"
                    :class="[isCurrentRoute(tab.url)
                        ? 'active-tab'
                        : 'hover:border-gray-300 hover:text-gray-700',
                        'whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm']"
                    :style="getTabStyles(tab.url)"
                >
                    {{ tab.text }}
                </Link>
            </div>

            <!-- Right Arrow -->
            <button
                @click="scrollTabs('right')"
                class="absolute right-0 top-0 bottom-0 z-10 bg-white px-2 hidden sm:flex items-center justify-center shadow group-hover:flex"
                v-show="canScrollRight"
            >
                ›
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const { buttonPrimaryBgColor } = useColors();

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

const currentTabUrl = computed(() => {
    const current = filteredTabs.value.find(tab => isCurrentRoute(tab.url));
    return current ? current.url : filteredTabs.value[0]?.url;
});

function onDropdownChange(e) {
    const url = e.target.value;
    if (url && url !== window.location.pathname) {
        window.location.href = url;
    }
}

// Scrollable tabs logic
const tabsContainer = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function scrollTabs(direction) {
    const container = tabsContainer.value;
    const scrollAmount = 150;
    if (direction === 'left') {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

function checkScroll() {
    const container = tabsContainer.value;
    canScrollLeft.value = container.scrollLeft > 0;
    canScrollRight.value = container.scrollLeft + container.clientWidth < container.scrollWidth;
}

const isDragging = ref(false);
let startX = 0;
let scrollLeft = 0;

function startDrag(e) {
    isDragging.value = true;
    startX = e.pageX - tabsContainer.value.offsetLeft;
    scrollLeft = tabsContainer.value.scrollLeft;
    tabsContainer.value.classList.add('cursor-grabbing');
}

function onDrag(e) {
    if (!isDragging.value) return;
    e.preventDefault();
    const x = e.pageX - tabsContainer.value.offsetLeft;
    const walk = (x - startX) * 1.5;
    tabsContainer.value.scrollLeft = scrollLeft - walk;
}

function stopDrag() {
    isDragging.value = false;
    tabsContainer.value.classList.remove('cursor-grabbing');
}

onMounted(() => {
    checkScroll();
});
</script>

<style scoped>
.active-tab {
    border-bottom-width: 2px;
}
/* Hide scrollbar */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
/* Drag cursor */
.cursor-grab {
    cursor: grab;
}
.cursor-grabbing {
    cursor: grabbing;
}
</style>
