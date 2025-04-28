<script setup>
import SidebarNavigation from "@/Components/Navigation/SidebarNavigation.vue";
import { computed } from "vue";

const sidebarLinks = computed(() => [
    {
        label: "General",
        url: `/app/settings`,
        icon: "mdi-view-dashboard-outline",
    },
    {
        label: "Style Kit",
        url: `/app/settings/style-kit`,
        icon: "mdi-palette-outline",
    },
    {
        label: "Typography",
        url: `/app/settings/typography`,
        icon: "mdi-format-letter-case",
    },
    {
        label: "Environment",
        url: `/app/settings/environment`,
        icon: "mdi-wrench-outline",
    },
    {
        label: "Database",
        url: `/app/settings/database`,
        icon: "mdi-database-outline",
    },
]);
</script>

<template>
    <SidebarNavigation :links="sidebarLinks" />
</template>
