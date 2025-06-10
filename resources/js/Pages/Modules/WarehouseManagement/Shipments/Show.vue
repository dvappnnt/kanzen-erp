<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";

const modelName = "shipments";

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    { label: "Email", value: "email", class: "text-gray-500" },
    {
        label: "Role",
        value: (row) => row.role,
        class: "text-gray-600 font-semibold",
    },
];

const contactDetails = ref([
    { label: "Located At", value: "address" },
    { label: "Mobile", value: "mobile" },
    { label: "Landline", value: "landline" },
    { label: "Description", value: "description" },
    { label: "Website", value: "website" },
]);

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${modelData.value.id}`,
        inertia: true,
        permission: "read suppliers"
    },
    {
        text: "Products Information",
        url: `/${modelName}/${modelData.value.id}/products`,
        inertia: true,
        permission: "read supplier products"
    },
]);
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <NavigationTabs :tabs="navigationTabs" />
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Contact Information"
                    :modelData="modelData"
                    :rowDetails="contactDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
