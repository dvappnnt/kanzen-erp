<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import Banner from "@/Components/Banner.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import ResponsiveNavigationMenu from "@/Components/Navigation/ResponsiveNavigationMenu.vue";
import SettingsDropdown from "@/Components/Navigation/SettingsDropdown.vue";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Navigation/Sidebar.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import Notification from "@/Components/Navigation/Notification.vue";
import { useColors } from "@/Composables/useColors";

defineProps({
    title: String,
});

const { appSettings } = usePage().props;
const { 
    navbarBgColor,
    navbarTextColor,
    navbarHoverBgColor,
    navbarHoverTextColor,
    navbarActiveColor,
    buttonPrimaryBgColor
} = useColors();

const navbarStyle = computed(() => ({
    backgroundColor: navbarBgColor.value,
    color: navbarTextColor.value
}));

const navbarTextStyle = computed(() => ({
    color: navbarTextColor.value
}));

const hoverNavbarItem = (event) => {
    event.currentTarget.style.backgroundColor = navbarHoverBgColor.value;
    event.currentTarget.style.color = navbarHoverTextColor.value;
};

const leaveNavbarItem = (event) => {
    event.currentTarget.style.backgroundColor = "transparent";
    event.currentTarget.style.color = navbarTextColor.value;
};

function stripQuotes(value) {
    return value && value.startsWith('"') && value.endsWith('"')
        ? value.slice(1, -1)
        : value;
}

const appIcon = computed(() => {
    const iconPayload = appSettings.icon ? stripQuotes(appSettings.icon) : null;
    return iconPayload && iconPayload !== "null"
        ? `/storage/${iconPayload}`
        : "/app-settings/app-icon.png";
});

const appLogo = computed(() => {
    const logoPayload = appSettings.logo ? stripQuotes(appSettings.logo) : null;
    return logoPayload && logoPayload !== "null"
        ? `/storage/${logoPayload}`
        : "/app-settings/app-logo.png";
});

const { permissions } = usePage().props;

const hasPermission = (permission) => {
    return permissions.includes(permission);
};

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(
        route("current-team.update"),
        {
            team_id: team.id,
        },
        {
            preserveState: false,
        }
    );
};

const logout = () => {
    router.post(route("logout"));
};

// Add ref for sidebar state
const isSidebarMinimized = ref(false);
const isMobileOpen = ref(false);

const handleSidebarChange = ({ isMinimized }) => {
    isSidebarMinimized.value = isMinimized;
};

const toggleMobileSidebar = () => {
    isMobileOpen.value = !isMobileOpen.value;
};
</script>

<template>
    <div>
        <Head :title="title" />

        <!-- Dynamic Favicon -->
        <Link rel="icon" :href="appIcon" type="image/png" />

        <Banner />

        <div class="min-h-screen bg-gray-100">
            <!-- Sidebar -->
            <Sidebar
                :is-mobile-open="isMobileOpen"
                :app-icon="appIcon"
                @update:sidebar="handleSidebarChange"
                @update:mobile="(value) => (isMobileOpen = value)"
            />

            <!-- Main Content -->
            <div
                :class="[
                    'transition-all duration-300',
                    'lg:pl-64',
                    isSidebarMinimized ? '!lg:pl-16' : '',
                    'min-h-screen',
                ]"
            >
                <nav
                    :style="navbarStyle"
                    class="sticky top-0 z-30 border-b border-gray-100"
                >
                    <!-- Primary Navigation Menu -->
                    <div
                        :style="navbarTextStyle"
                        class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8"
                    >
                        <div class="flex justify-between h-16">
                            <div class="flex">
                                <!-- Logo and App Name -->
                                <div class="flex items-center">
                                    <!-- <Link :href="route('dashboard')" class="flex items-center space-x-3">
                                        <ApplicationMark :logo="appIcon" class="block h-9 w-auto" />
                                        <span class="hidden sm:block text-lg font-semibold" :style="navbarTextStyle">
                                            {{ appSettings.name ? stripQuotes(appSettings.name) : "The EO" }}
                                        </span>
                                    </Link> -->
                                </div>

                                <!-- Mobile App Name -->
                                <div
                                    class="flex items-center sm:hidden px-2 text-lg font-semibold"
                                    :style="navbarTextStyle"
                                >
                                    {{
                                        appSettings.name
                                            ? stripQuotes(appSettings.name)
                                            : "The EO"
                                    }}
                                </div>
                            </div>

                            <div class="hidden sm:flex sm:items-center sm:ms-6">
                                <div class="ms-3 relative">
                                </div>

                                <!-- Notification Icon -->
                                <Notification
                                    :navbar-text-color="navbarTextColor"
                                    :on-hover="hoverNavbarItem"
                                    :on-leave="leaveNavbarItem"
                                    :primary-color="buttonPrimaryBgColor"
                                />

                                <!-- Settings Dropdown -->
                                <SettingsDropdown
                                    :navbar-text-style="navbarTextStyle"
                                    :on-logout="logout"
                                />
                            </div>

                            <!-- Hamburger -->
                            <div class="-me-2 flex items-center sm:hidden">
                                <button
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                                    @click="toggleMobileSidebar"
                                >
                                    <svg
                                        class="size-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            :class="{
                                                hidden: isMobileOpen,
                                                'inline-flex': !isMobileOpen,
                                            }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            :class="{
                                                hidden: !isMobileOpen,
                                                'inline-flex': isMobileOpen,
                                            }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white shadow">
                    <div class="max-w-12xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main class="py-6">
                    <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8">
                        <!-- Add Breadcrumbs here -->
                        <Breadcrumbs />
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
