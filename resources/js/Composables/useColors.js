import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useColors() {
    const { appSettings } = usePage().props;

    const stripQuotes = (value) => {
        return value && value.startsWith('"') && value.endsWith('"')
            ? value.slice(1, -1)
            : value;
    };

    // Navbar Colors
    const navbarBgColor = computed(() => appSettings?.navbar_bg_color ? stripQuotes(appSettings.navbar_bg_color) : "#FFFFFF");
    const navbarTextColor = computed(() => appSettings?.navbar_text_color ? stripQuotes(appSettings.navbar_text_color) : "#000000");
    const navbarHoverBgColor = computed(() => appSettings?.navbar_hover_bg_color ? stripQuotes(appSettings.navbar_hover_bg_color) : "#F3F4F6");
    const navbarHoverTextColor = computed(() => appSettings?.navbar_hover_text_color ? stripQuotes(appSettings.navbar_hover_text_color) : "#000000");
    const navbarActiveBgColor = computed(() => appSettings?.navbar_active_bg_color ? stripQuotes(appSettings.navbar_active_bg_color) : "#F3F4F6");
    const navbarActiveTextColor = computed(() => appSettings?.navbar_active_text_color ? stripQuotes(appSettings.navbar_active_text_color) : "#000000");

    // Sidebar Colors
    const sidebarBgColor = computed(() => appSettings?.sidebar_bg_color ? stripQuotes(appSettings.sidebar_bg_color) : "#FFFFFF");
    const sidebarTextColor = computed(() => appSettings?.sidebar_text_color ? stripQuotes(appSettings.sidebar_text_color) : "#000000");
    const sidebarHoverBgColor = computed(() => appSettings?.sidebar_hover_bg_color ? stripQuotes(appSettings.sidebar_hover_bg_color) : "#F3F4F6");
    const sidebarHoverTextColor = computed(() => appSettings?.sidebar_hover_text_color ? stripQuotes(appSettings.sidebar_hover_text_color) : "#000000");
    const sidebarActiveBgColor = computed(() => appSettings?.sidebar_active_bg_color ? stripQuotes(appSettings.sidebar_active_bg_color) : "#F3F4F6");
    const sidebarActiveTextColor = computed(() => appSettings?.sidebar_active_text_color ? stripQuotes(appSettings.sidebar_active_text_color) : "#000000");

    // Button Colors
    const buttonPrimaryBgColor = computed(() => appSettings?.button_primary_bg_color ? stripQuotes(appSettings.button_primary_bg_color) : "#4350b1");
    const buttonPrimaryTextColor = computed(() => appSettings?.button_primary_text_color ? stripQuotes(appSettings.button_primary_text_color) : "#FFFFFF");

    // Input Colors
    const inputActiveBgColor = computed(() => appSettings?.input_active_bg_color ? stripQuotes(appSettings.input_active_bg_color) : "#F3F4F6");

    // Theme Colors
    const primaryColor = computed(() => appSettings?.primary_color ? stripQuotes(appSettings.primary_color) : "#4350b1");
    const secondaryColor = computed(() => appSettings?.secondary_color ? stripQuotes(appSettings.secondary_color) : "#6B7280");
    const successColor = computed(() => appSettings?.success_color ? stripQuotes(appSettings.success_color) : "#10B981");
    const dangerColor = computed(() => appSettings?.danger_color ? stripQuotes(appSettings.danger_color) : "#EF4444");
    const warningColor = computed(() => appSettings?.warning_color ? stripQuotes(appSettings.warning_color) : "#F59E0B");
    const infoColor = computed(() => appSettings?.info_color ? stripQuotes(appSettings.info_color) : "#3B82F6");

    return {
        // Navbar Colors
        navbarBgColor,
        navbarTextColor,
        navbarHoverBgColor,
        navbarHoverTextColor,
        navbarActiveBgColor,
        navbarActiveTextColor,

        // Sidebar Colors
        sidebarBgColor,
        sidebarTextColor,
        sidebarHoverBgColor,
        sidebarHoverTextColor,
        sidebarActiveBgColor,
        sidebarActiveTextColor,

        // Button Colors
        buttonPrimaryBgColor,
        buttonPrimaryTextColor,

        // Input Colors
        inputActiveBgColor,

        // Theme Colors
        primaryColor,
        secondaryColor,
        successColor,
        dangerColor,
        warningColor,
        infoColor,

        // Utility
        stripQuotes,
    };
} 